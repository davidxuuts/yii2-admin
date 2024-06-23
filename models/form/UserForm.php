<?php
/*
 * Copyright (c) 2023.
 * @author David Xu <david.xu.uts@163.com>
 * All rights reserved.
 */

namespace davidxu\admin\models\form;

use yii\web\User;
use davidxu\admin\models\searchs\Assignment;
use yii\base\Exception;
use yii\base\Model;
use Yii;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;

class UserForm extends Model
{
    public string|int|null $id = null;

    public ?string $password = null;

    public ?string $username = null;

    public ?string $realname = null;

    public string|array|null $roles = [];

    public bool $isNewUser = false;

    private ActiveRecord|ActiveRecordInterface|null|User|\davidxu\admin\models\User $_user;

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['username', 'roles'], 'required'],
            [['realname'], 'string', 'max' => 50],
            [['password'], 'string', 'min' => 6],
            ['roles', 'safe'],
//            ['roles', 'in', 'range' => Item::find()->select(['name'])->where([
//                'type' => RbacItem::TYPE_ROLE,
//            ])->column(),
//                'allowArray' => true
//            ],
            ['username', 'isUnique'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return [
            'password' => Yii::t('srbac', 'Password'),
            'username' => Yii::t('srbac', 'Username'),
            'realname' => Yii::t('srbac', 'Realname'),
            'roles' => Yii::t('srbac', 'Roles'),
        ];
    }

    /**
     * Load default _user attributes
     * @return void
     */
    public function loadData(): void
    {
        if ($this->_user = Yii::$app->user->identity) {
            $this->username = $this->_user->username;
            $this->roles = $this->getRoles();
        } else {
            $this->_user = new Yii::$app->user->identityClass;
            $this->isNewUser = true;
        }
    }

    /**
     * Attribute [[username]] unique validation
     * @return void
     */
    public function isUnique(): void
    {
        /** @var User $modelClass */
        $modelClass = Yii::$app->user->identityClass;

        if ($modelClass instanceof User || $modelClass instanceof \davidxu\admin\models\User) {
            $member = $modelClass::findByUsername($this->username);
        } else {
            $member = null;
        }

        /** @var User $member */
        if (($member instanceof User) && $member->id !== (int)$this->id) {
            $this->addError('username', Yii::t('app', 'Username has been token'));
        }
    }

    /**
     * Save user instance
     * @return bool
     */
    public function save(): bool
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $member = $this->_user;
            if ($this->isNewUser) {
                $member->auth_key = Yii::$app->security->generateRandomString();
                if (empty($this->password)) {
                    $this->password = Yii::$app->security->generateRandomString(10);
                }
                $member->password_hash = Yii::$app->security->generatePasswordHash($this->password);
            } else if (!(
                empty($this->password)
                || Yii::$app->security->validatePassword($this->password, $member->password_hash)
            )) {
                $member->password_hash = Yii::$app->security->generatePasswordHash($this->password);
            }

            $member->username = $this->username;

            if (!$member->save()) {
                $this->addErrors($member->getErrors());
                $transaction->rollBack();
                return false;
            }
            $member->refresh();
            Assignment::deleteAll(['user_id' => $member->id]);
            foreach ($this->roles as $item_name) {
                $authAssigment = new Assignment([
                    'user_id' => $member->id,
                    'item_name' => $item_name,
                ]);
                if (!($authAssigment->save())) {
                    $this->addErrors($authAssigment->getErrors());
                    $transaction->rollBack();
                    return false;
                }
            }
            $transaction->commit();
            return true;
        } catch (Exception $exception) {
            Yii::info($exception->getMessage());
            $transaction->rollBack();
            return false;
        }
    }

    /**
     * @param string $type
     * @return array|ActiveRecord[]
     */
    protected function getRoles(string $type = 'array'): array
    {
        $roles = Assignment::find()->where(['user_id' => $this->id])->all();
        if (!$roles) {
            $roles = [];
        }
        if ($type === 'array') {
            $items = [];
            if ($roles) {
                foreach ($roles as $role) {
                    /** @var Assignment $role */
                    $items[] = $role->item_name;
                }
            }
            return $items;
        } else {
            return $roles;
        }
    }
}

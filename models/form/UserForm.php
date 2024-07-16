<?php
/*
 * Copyright (c) 2023.
 * @author David Xu <david.xu.uts@163.com>
 * All rights reserved.
 */

namespace davidxu\admin\models\form;

use yii\base\InvalidConfigException;
use yii\web\User;
use davidxu\admin\models\Assignment;
use yii\base\Exception;
use yii\base\Model;
use Yii;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;
use davidxu\admin\models\Item;
use yii\rbac\Item as RbacItem;

class UserForm extends Model
{
    public string|int|null $id = null;
    public ?string $password = null;
    public ?string $username = null;
    public ?string $realname = null;
    public ?string $email = null;

    public string|array|null $roles = [];

    public bool $isNewUser = false;

    private ActiveRecord|ActiveRecordInterface|null|User|\davidxu\admin\models\User $_user;

    const SCENARIO_CREATE = 'create';

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['username', 'realname'], 'required'],
            [['password'], 'required', 'on' => self::SCENARIO_CREATE],
            [['realname'], 'string', 'max' => 50],
            [['password'], 'string', 'min' => 6],
            [['email'], 'string', 'max' => 255],
            [['email'], 'email'],
            ['roles', 'safe'],
            ['roles', 'in', 'range' => Item::find()->select(['name'])->where([
                'type' => RbacItem::TYPE_ROLE,
            ])->column(),
                'allowArray' => true
            ],
            ['username', 'isUnique'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLabels(): array
    {
        return [
            'password' => Yii::t('rbac-admin', 'Password'),
            'username' => Yii::t('rbac-admin', 'Username'),
            'realname' => Yii::t('rbac-admin', 'Realname'),
            'roles' => Yii::t('rbac-admin', 'Roles'),
        ];
    }

    /**
     * Load default _user attributes
     * @return void
     * @throws InvalidConfigException
     */
    public function loadData(): void
    {
        $modelClass = new Yii::$app->user->identityClass;
        if ($this->id) {
            $this->_user = $modelClass::find()
                ->where([
                    'id' => $this->id,
                ])->one();
            $this->username = $this->_user->username;
            $this->realname = $this->_user->realname;
            $this->email = $this->_user->email;
            $this->roles = $this->getRoles();
        } else {
            $this->_user = new $modelClass;
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
            $this->addError('username', Yii::t('rbac-admin', 'Username has been token'));
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
            $member->realname = $this->realname;
            $member->email = $this->email;

            if (!$member->save()) {
                $this->addErrors($member->getErrors());
                $transaction->rollBack();
                return false;
            }
            $member->refresh();
            Assignment::deleteAll(['user_id' => $member->id]);
            foreach ($this->roles as $item_name) {
                $authAssigment = new Assignment();
                $authAssigment->user_id = $member->id;
                $authAssigment->item_name = $item_name;
                if (!($authAssigment->save())) {
                    $this->addErrors($authAssigment->getErrors());
                    $transaction->rollBack();
                    return false;
                }
            }
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            if (YII_ENV_DEV) {
                echo 'Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
                echo $e->getTraceAsString() . "\n";
            }
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

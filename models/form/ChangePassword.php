<?php

namespace davidxu\admin\models\form;

use Yii;
use davidxu\admin\models\User;
use yii\base\Model;
use yii\db\Exception;

/**
 * Description of ChangePassword
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ChangePassword extends Model
{
    public ?string $oldPassword = null;
    public ?string $newPassword = null;
    public ?string $retypePassword = null;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['oldPassword', 'newPassword', 'retypePassword'], 'required'],
            [['oldPassword'], 'validatePassword'],
            [['newPassword'], 'string', 'min' => 6],
            [['retypePassword'], 'compare', 'compareAttribute' => 'newPassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     */
    public function validatePassword(): void
    {
        /* @var $user User */
        $user = Yii::$app->user->identity;
        if (!$user || !$user->validatePassword($this->oldPassword)) {
            $this->addError('oldPassword', 'Incorrect old password.');
        }
    }

    /**
     * Change password.
     *
     * @return User|bool|null the saved model or null if saving fails
     * @throws Exception
     */
    public function change(): User|bool|null
    {
        if ($this->validate()) {
            /* @var $user User */
            $user = Yii::$app->user->identity;
            $user->setPassword($this->newPassword);
            $user->generateAuthKey();
            if ($user->save()) {
                return true;
            }
        }

        return false;
    }
}

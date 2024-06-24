<?php
namespace davidxu\admin\models\form;

use davidxu\admin\components\UserStatus;
use davidxu\admin\models\User;
use mdm\admin\models\form\ResetPassword;
use Yii;
use yii\base\Exception;
use yii\base\Model;

/**
 * Password reset request form
 */
class PasswordResetRequest extends Model
{
    public ?string $email = null;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $class = Yii::$app->getUser()->identityClass ? : 'davidxu\admin\models\User';
        return [
            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'exist',
                'targetClass' => $class,
                'filter' => ['status' => UserStatus::ACTIVE],
                'message' => 'There is no user with such email.'
            ],
        ];
    }

    /**
     * Sends an email with a link, for resetting the password.
     *
     * @return boolean whether the email was sent
     * @throws Exception
     */
    public function sendEmail(): bool
    {
        /* @var $user User */
        $class = Yii::$app->getUser()->identityClass ? : 'davidxu\admin\models\User';
        $user = $class::findOne([
            'status' => UserStatus::ACTIVE,
            'email' => $this->email,
        ]);

        if ($user) {
            if (!ResetPassword::isPasswordResetTokenValid($user->password_reset_token)) {
                $user->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
            }

            if ($user->save()) {
                return Yii::$app->mailer->compose(['html' => 'passwordResetToken-html', 'text' => 'passwordResetToken-text'], ['user' => $user])
                    ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
                    ->setTo($this->email)
                    ->setSubject('Password reset for ' . Yii::$app->name)
                    ->send();
            }
        }

        return false;
    }
}

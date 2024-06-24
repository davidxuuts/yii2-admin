<?php

namespace davidxu\admin\models\form;

use davidxu\admin\components\UserStatus;
use davidxu\admin\models\User;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

/**
 * Password reset form
 */
class ResetPassword extends Model
{
    public ?string $password = null;
    public ?string $retypePassword = null;
    /**
     * @var ?User
     */
    private ?User $_user;

    /**
     * Creates a form model given a token.
     *
     * @param string $token
     * @param array $config name-value pairs that will be used to initialize the object properties
     * @throws \Exception
     */
    public function __construct($token, array $config = [])
    {
        if (empty($token) || !is_string($token)) {
            throw new InvalidArgumentException('Password reset token cannot be blank.');
        }
        // check token
        $class = Yii::$app->getUser()->identityClass ?: 'davidxu\admin\models\User';
        if (static::isPasswordResetTokenValid($token)) {
            $this->_user = $class::findOne([
                    'password_reset_token' => $token,
                    'status' => UserStatus::ACTIVE
            ]);
        }
        if (!$this->_user) {
            throw new InvalidArgumentException('Wrong password reset token.');
        }
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['password', 'retypePassword'], 'required'],
            ['password', 'string', 'min' => 6],
            ['retypePassword', 'compare', 'compareAttribute' => 'password']
        ];
    }

    /**
     * Resets password.
     *
     * @return boolean if the password was reset.
     * @throws Exception
     */
    public function resetPassword(): bool
    {
        $user = $this->_user;
        $user->setPassword($this->password);
        $user->removePasswordResetToken();

        return $user->save(false);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     * @throws \Exception
     */
    public static function isPasswordResetTokenValid(string $token): bool
    {
        if (empty($token)) {
            return false;
        }
        $expire = ArrayHelper::getValue(Yii::$app->params, 'user.passwordResetTokenExpire', 24 * 3600);
        $parts = explode('_', $token);
        $timestamp = (int) end($parts);
        return $timestamp + $expire >= time();
    }
}

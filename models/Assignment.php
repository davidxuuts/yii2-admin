<?php

namespace davidxu\admin\models;

use davidxu\admin\BaseObject;
use davidxu\admin\components\Configs;
use davidxu\admin\components\Helper;
use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\IdentityInterface;

/**
 * Description of Assignment
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 2.5
 */
class Assignment extends BaseObject
{
    /**
     * @var int|array User id
     */
    public int|array $id;
    /**
     * @var IdentityInterface User
     */
    public mixed $user;

    /**
     * @inheritdoc
     */
    public function __construct($id, $user = null, $config = array())
    {
        $this->id = $id;
        $this->user = $user;
        parent::__construct($config);
    }

    /**
     * Grands a roles from a user.
     * @param array $items
     * @return integer number of successful grand
     * @throws InvalidConfigException
     */
    public function assign(array $items): int
    {
        $manager = Configs::authManager();
        $success = 0;
        if (count($items)) {
            foreach ($items as $name) {
                try {
                    $item = $manager->getRole($name);
                    $item = $item ?: $manager->getPermission($name);
                    $manager->assign($item, $this->id);
                    $success++;
                } catch (Exception $exc) {
                    Yii::error($exc->getMessage(), __METHOD__);
                }
            }
            Helper::invalidate();
        }
//        Helper::invalidate();
        return $success;
    }

    /**
     * Revokes a roles from a user.
     * @param array $items
     * @return integer number of successful revokes
     * @throws InvalidConfigException
     */
    public function revoke(array $items): int
    {
        $manager = Configs::authManager();
        $success = 0;
        if (count($items)) {
            foreach ($items as $name) {
                try {
                    $item = $manager->getRole($name);
                    $item = $item ?: $manager->getPermission($name);
                    $manager->revoke($item, $this->id);
                    $success++;
                } catch (Exception $exc) {
                    Yii::error($exc->getMessage(), __METHOD__);
                }
            }
        }
        Helper::invalidate();
        return $success;
    }

    /**
     * Get all available and assigned roles/permission
     * @return array
     * @throws InvalidConfigException
     */
    public function getItems(): array
    {
        $manager = Configs::authManager();
        $available = [];
        foreach (array_keys($manager->getRoles()) as $name) {
            $available[$name] = 'role';
        }

        foreach (array_keys($manager->getPermissions()) as $name) {
            if ($name[0] != '/') {
                $available[$name] = 'permission';
            }
        }

        $assigned = [];
        foreach ($manager->getAssignments($this->id) as $item) {
            $assigned[$item->roleName] = $available[$item->roleName];
            unset($available[$item->roleName]);
        }

        ksort($available);
        ksort($assigned);
        return [
            'available' => $available,
            'assigned' => $assigned,
        ];
    }

    /**
     * @inheritdoc
     * @param $name
     * @return mixed|void
     */
    public function __get($name)
    {
        if ($this->user) {
            return $this->user->$name;
        }
    }
}

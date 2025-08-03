<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "browser".
 *
 * @property int $id
 * @property string $name
 * @property string|null $version
 *
 * @property Log[] $logs
 */
class Browser extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'browser';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['version'], 'default', 'value' => null],
            [['name'], 'required'],
            [['name', 'version'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'version' => 'Version',
        ];
    }

    /**
     * Gets query for [[Logs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLogs()
    {
        return $this->hasMany(Log::class, ['browser_id' => 'id']);
	}

	public static function findOrCreate($name, $version)
    {
        $model = static::find()
            ->where(['name' => $name, 'version' => $version])
            ->one();
            
        if (!$model) {
            $model = new static();
            $model->name = $name;
            $model->version = $version;
            $model->save();
        }
        
        return $model;
    }
}

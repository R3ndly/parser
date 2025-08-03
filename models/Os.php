<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "os".
 *
 * @property int $id
 * @property string $name
 * @property string $architecture
 *
 * @property Log[] $logs
 */
class Os extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'architecture'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['architecture'], 'string', 'max' => 10],
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
            'architecture' => 'Architecture',
        ];
    }

    /**
     * Gets query for [[Logs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLogs()
    {
        return $this->hasMany(Log::class, ['os_id' => 'id']);
    }

	public static function findOrCreate($name, $architecture)
    {
        $model = static::find()
            ->where(['name' => $name, 'architecture' => $architecture])
            ->one();
            
        if (!$model) {
            $model = new static();
            $model->name = $name;
            $model->architecture = $architecture;
            $model->save();
        }
        
        return $model;
    }
}

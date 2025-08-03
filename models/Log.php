<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "log".
 *
 * @property int $id
 * @property string $ip
 * @property string $request_date
 * @property int $url_id
 * @property int|null $browser_id
 * @property int|null $os_id
 *
 * @property Browser $browser
 * @property Os $os
 * @property Url $url
 */
class Log extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
		return [
            [['ip', 'request_date', 'url_id'], 'required'],
            [['request_date'], 'safe'],
            [['url_id', 'browser_id', 'os_id'], 'integer'],
            [['ip'], 'string', 'max' => 45],
            [['browser_id'], 'exist', 'skipOnError' => true, 'targetClass' => Browser::class, 'targetAttribute' => ['browser_id' => 'id']],
            [['os_id'], 'exist', 'skipOnError' => true, 'targetClass' => Os::class, 'targetAttribute' => ['os_id' => 'id']],
            [['url_id'], 'exist', 'skipOnError' => true, 'targetClass' => Url::class, 'targetAttribute' => ['url_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'Ip',
            'request_date' => 'Request Date',
            'url_id' => 'Url ID',
            'browser_id' => 'Browser ID',
            'os_id' => 'Os ID',
        ];
    }

    /**
     * Gets query for [[Browser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBrowser()
    {
        return $this->hasOne(Browser::class, ['id' => 'browser_id']);
    }

    /**
     * Gets query for [[Os]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOs()
    {
        return $this->hasOne(Os::class, ['id' => 'os_id']);
    }

    /**
     * Gets query for [[Url]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUrl()
    {
        return $this->hasOne(Url::class, ['id' => 'url_id']);
    }

}

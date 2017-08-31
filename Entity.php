<?php

abstract class Entity extends CActiveRecord
{

    /**
     * pk:true,type:int,
     */
    public $id;

    /**
     * type:int
     */
    public $id_user_created;

    /**
     * type:int
     */
    public $id_user_updated;

    /**
     * type:datetime,default:CURRENT_TIMESTAMP
     */
    public $datetime_create;

    /**
     * type:datetime
     */
    public $datetime_update;

    public function beforeSave()
    {
        if (parent::beforeSave()) {
            if ($this->isNewRecord) {
                $this->id_user_create  = isset(Yii::app()->user) ? Yii::app()->user->id : null;
                $this->datetime_create = date("Y-m-d H:i:s");
            } else {
                $this->id_user_update  = isset(Yii::app()->user) ? Yii::app()->user->id : null;
                $this->datetime_update = date("Y-m-d H:i:s");
            }
            return true;
        }
        return false;
    }
    
    
    public function hasFK($columnName)
    {
        return in_array($columnName, array_keys($this->getTableSchema()->foreignKeys));
    }

    public function createTable()
    {
        if (Yii::app()->db->schema->getTable($this->tableName()) == false) {
            Yii::app()->db->createCommand("CREATE TABLE " . $this->tableName() . " (id int AUTO_INCREMENT PRIMARY KEY);")->execute();
        }
        return true;
    }

    public function getTableColumns()
    {
        if ($this->createTable()) {
            return array_keys(Yii::app()->db->schema->getTable($this->tableName())->columns);
        }
        return false;
    }

    public function createRelations()
    {
        foreach ($this->relations() as $relation) {
            if ($relation[0] == self::BELONGS_TO) {
                $relatedClass = new $relation[1](false);
                if ($this->hasFK($relation[2]) == false) {
                    Yii::app()->db->createCommand("ALTER TABLE " . $this->tableName() . " ADD FOREIGN KEY (" . $relation[2] . ") REFERENCES " . $relatedClass->tableName() . "(" . $relatedClass->pkName() . ")")->execute();
                }
            }
        }
    }
}

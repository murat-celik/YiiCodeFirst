<?php

class EntityManagerComponent extends CApplicationComponent
{

    private $_files;
    private $_modules;
    private $_declared_classes;

    private function setModulesFilesAutoload()
    {

        if (isset($this->_modules) == false) {
            $this->_modules = Yii::app()->getModules();
        }

        if (!empty($this->_modules)) {
            foreach ($this->_modules as $key => $module) {

                if (is_dir(Yii::app()->basePath . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR . 'models')) {
                    $moduleFiles = scandir(Yii::app()->basePath . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR . 'models');

                    foreach ($moduleFiles as $file) {
                        if (strlen($file) > 4) { //.php
                            include_once(Yii::app()->basePath . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $file);
                        }
                    }
                }
            }
        }
    }

    private function setFilesAutoload()
    {
        if (is_dir(Yii::app()->basePath . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR)) {

            if (isset($this->_files) == false) {
                $this->_files = scandir(Yii::app()->basePath . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR);
            }
            
            foreach ($this->_files as $file) {
                if (strlen($file) > 4) { //.php
                    include_once(Yii::app()->basePath . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $file);
                }
            }
        }

        $this->setModulesFilesAutoload();
    }

    public function setDeclaredClasses(){
        $this->_declared_classes = get_declared_classes();
    }
    
    private function createRetations(){

        foreach ($this->_declared_classes as $class) {
            if (is_subclass_of($class, 'Entity')) {
                $model = new $class(null);
                if ($model->createTable()) {
                    $model->createRelations();
                }
            }
            unset($model);
            unset($entity);
        }
    }

    public function updateDatabase()
    {
        $this->setFilesAutoload();
        $this->setDeclaredClasses();

        foreach ($this->_declared_classes as $class) {

            if (is_subclass_of($class, 'Entity')) {
                $entity = new ReflectionClass($class);
                $model = new $class(null);

                if ($model->createTable()) {
                    $tableColumns = $model->getTableColumns();
                    $tableColumnsQuery = '';

                    foreach ($entity->getProperties() as $item) {

                        if ($item->name != 'db' && in_array($item->name, $tableColumns) == false) {

                            $columnQuery = "ALTER TABLE " . $model->tableName() . " ADD " . $item->name;
                            $itemAttributes = array_filter(explode(',', str_replace('/', '', str_replace('*', '', $item->getDocComment()))));

                            if (!empty($itemAttributes)) {
                                foreach ($itemAttributes as $attribute) {

                                    $attribute = explode(':', $attribute);

                                    switch (trim($attribute[0])) {
                                        case 'type':
                                            $columnQuery = $columnQuery . " " . trim($attribute[1]);
                                            break;
                                        case 'pk':
                                            $columnQuery = $columnQuery . ' AUTO_INCREMENT PRIMARY KEY';
                                            break;
                                        case 'unique':
                                            $columnQuery = $columnQuery . ' UNIQUE ';
                                            break;
                                        case 'collation':
                                            $columnQuery = $columnQuery . " COLLATE " . trim($attribute[1]);
                                            break;
                                        case 'nullable':
                                            $columnQuery = $columnQuery . " " . trim($attribute[1]);
                                            break;
                                        case 'default':
                                            $columnQuery = $columnQuery . " DEFAULT " . trim($attribute[1]);
                                            break;
                                        case 'comments':
                                            $columnQuery = $columnQuery . " COMMENT " . trim($attribute[1]);
                                            break;
                                    }
                                }

                                $tableColumnsQuery[] = $columnQuery;
                            }
                        }
                    }

                    if (!empty($tableColumnsQuery)) {
                        Yii::app()->db->createCommand(implode(';', $tableColumnsQuery))->execute();
                    }

                }
            }
            unset($model);
            unset($entity);
        }

        $this->createRetations();
    }
}

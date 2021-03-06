<?php

namespace OutSource\Console\Generators\YafGenerator;

use OutSource\Console\Common\CommandData;
use OutSource\Console\Generators\Contracts\GeneratorInterface;
use OutSource\Console\Utils\FileUtils;
use OutSource\FileSystem\FileSystem;

class ModelGenerator implements GeneratorInterface
{

    protected $commandConfig;
    protected $commandData;

    protected $files;

    public function __construct($commandConfig,CommandData $commandData,Filesystem $file)
    {
        $this->commandConfig = $commandConfig;
        $this->commandData = $commandData;
        $this->files = $file;
    }

    /**
     * 生成函数
     */
    public function generateModel()
    {
        $modelName = $this->commandConfig->modelName;
        $modelFileName = ucfirst($modelName).'.php';
        $templateData = FileUtils::getTemplateScaffoldPath($this->commandConfig->get('systemTemplates'), 'models.stub');
        $templateData = str_replace('$MODEL_NAME$', ucfirst($modelName).'Model', $templateData);

        $templateData = str_replace(
            '$TABLE_NAME$',
            $this->commandConfig->tableName,
            $templateData
        );
        $templateData = str_replace(
            '$SOFTDELETE$',
            $this->commandConfig->softDelete ? : "false",
            $templateData
        );


        $templateData = $this->fillable($this->commandData->fields, $templateData);
        $templateData = $this->fillRule($this->commandData->fields, $templateData);
        $templateData = $this->fillPrimaryName($this->commandConfig, $this->commandData, $templateData);

        FileUtils::createFile(
            $this->commandConfig->get('model'),
            $modelFileName,
            $templateData
        );

        $this->commandData->commandComment("\nModel created: ");
        $this->commandData->commandInfo($modelFileName);

    }

    public function fillPrimaryName($commandConfig , $commandData , $templateData)
    {
        if($commandConfig->get('primaryName')) {
            $templateData = str_replace('$TABLE_ID$', $commandConfig->get('primaryName'), $templateData);
            return $templateData;
        }

        foreach ($commandData->fields as $field) {
            if($field->isPrimary) {
                $templateData = str_replace('$TABLE_ID$', $field->name, $templateData);
                return $templateData;
            }
        }
    }
    
    public function fillable($fillables , $templateData)
    {
        $fillable = [];
        foreach ($fillables as $item) {
            if(!$item->isPrimary) {
                $fillable[] = "'".$item->name."'";
            }
        }

        $templateData = str_replace('$FILLABLE$', implode(','.infy_nl_tab(1, 2), $fillable), $templateData);
        return $templateData;
    }

    public function fillRule($fillables , $templateData)
    {
        $rules = [];
        foreach ($fillables as $fillable) {
            if (!empty($fillable->validations)) {
                $rule = "'".$fillable->name."' => '".$fillable->validations."'";
                $rules[] = $rule;
            }
        }
        $templateData = str_replace('$RULES$', implode(','.infy_nl_tab(1, 2), $rules), $templateData);
        return $templateData;
    }


    public function rollback()
    {
        $modelName = $this->commandData->modelName;
        $fileName = 'M_'.ucfirst($modelName).'.php';
        FileUtils::deleteFile(
            $this->commandConfig->get('model').$this->commandConfig->get('modules'),
            $fileName
        );
        $this->commandData->commandComment($fileName." delete ");
    }

    /**
     * 生成函数
     */
    public function generate()
    {
        // TODO: Implement generate() method.
    }
}
<?php

namespace OutSource\Console\Generators\CIGenerator;

use OutSource\Console\Common\CommandData;
use OutSource\Console\Generators\Contracts\GeneratorInterface;
use OutSource\Console\Utils\FileUtils;
use OutSource\Console\Utils\HTMLFieldGenerator;
use OutSource\FileSystem\FileSystem;

class ViewGenerator implements GeneratorInterface
{

    protected $commandConfig;
    protected $commandData;

    protected $files;
    protected $Urls;
    protected $viewPath;

    protected $includeResource;

    public function __construct($commandConfig, CommandData $commandData, Filesystem $file)
    {
        $this->commandConfig = $commandConfig;
        $this->commandData = $commandData;
        $this->files = $file;

    }

    public function generate()
    {

        $modelName = $this->commandData->modelName;
        $this->viewPath = $this->commandConfig->get('views') . $this->commandConfig->get('modules') . ucfirst($modelName) ;

        $this->Urls = [
            'INDEX_RUL' => $this->commandConfig->get('modules') . ucfirst($modelName) . DIRECTORY_SEPARATOR . 'index',
            'CREATE_RUL' => $this->commandConfig->get('modules') . ucfirst($modelName) . DIRECTORY_SEPARATOR . 'create',
            'DESTORY_URL' => $this->commandConfig->get('modules') . ucfirst($modelName) . DIRECTORY_SEPARATOR . 'destory' ,
            'LIST_URL' => $this->commandConfig->get('modules') . ucfirst($modelName) . DIRECTORY_SEPARATOR . 'list' ,
            'EDIT_URL' => $this->commandConfig->get('modules') . ucfirst($modelName) . DIRECTORY_SEPARATOR . 'edit' . DIRECTORY_SEPARATOR,
            'SOTRE_URL' => $this->commandConfig->get('modules') . ucfirst($modelName) . DIRECTORY_SEPARATOR . 'store',
            'UPDATE_URL' => $this->commandConfig->get('modules') . ucfirst($modelName) . DIRECTORY_SEPARATOR . 'update'
        ];
        $this->generateDirectory();

        $this->generateIndex();
        $this->generateCreate();
        $this->generateEdit();

    }

    public function rollback()
    {
        $modelName = $this->commandData->modelName;
        $this->viewPath = $this->commandConfig->get('views') . $this->commandConfig->get('modules') . $modelName ;
        $files = [
            'edit.php',
            'index.php',
            'create.php',
        ];
        foreach ($files as $file) {
            $result = FileUtils::deleteFile( $this->viewPath . DIRECTORY_SEPARATOR , $file);
            $this->commandData->commandComment($file." delete");
        }
    }

    private function generateEdit()
    {
        $templatePath = $this->commandConfig->getViewsPath('View/edit.stub');
        $templateData = $this->files->get($templatePath);
        $fields = array();
        $resource = array();
        $editfile = "";
        foreach ($this->commandData->fields as $field) {
            if($field->htmlType && $field->isFillable ){
                $fields[] = HTMLFieldGenerator::generateHTML($field , $this->commandConfig , $this->files);
                $resource[] = $this->includeFileResource($field);
            }

            if($field->htmlType == "file") {
                $path = $this->commandConfig->getViewsPath('Fields/file_edit_resource.stub');
                $editFileData =  $this->files->get($path);
                $editfile = str_replace('$FILEIMGID$' , $field->name , $editFileData);
            }
        }

        $templateData = str_replace('$FILEDS$' , implode(PHP_EOL.PHP_EOL, $fields) , $templateData);
        $templateData = str_replace('$INDEX_RUL$' , $this->Urls['INDEX_RUL'] , $templateData);
        $templateData = str_replace('$STORE_URL$' , $this ->Urls['SOTRE_URL'] , $templateData);
        $templateData = str_replace('$FILEDID$' ,  sprintf('<?php echo @$data["%s"]?>' , $this->commandData->getModelPrimaryKey()) , $templateData);
        $templateData = str_replace('$PRIMARY$' ,  $this->commandData->getModelPrimaryKey() , $templateData);
        $templateData = str_replace('$UPDATE_URL$' ,  $this->Urls['UPDATE_URL'] , $templateData);
        $templateData = str_replace('$RESOURCE$' , implode(PHP_EOL.PHP_EOL, $resource)  , $templateData);
        //todo 处理下files
        $templateData = str_replace('$ISLOADFILED$' , empty($editfile) ? "" : $editfile, $templateData);


        FileUtils::createFile(
            $this->viewPath . DIRECTORY_SEPARATOR,
            "edit.php",
            $templateData
        );

        $this->commandData->commandComment("Edit.php created: ");
    }

    private function generateCreate()
    {
        $templatePath = $this->commandConfig->getViewsPath('View/create.stub');
        $templateData = $this->files->get($templatePath);
        $fields = array();
        $resource = array();

        foreach ($this->commandData->fields as $field) {
            if($field->htmlType && $field->isFillable ){
                $fields[] = HTMLFieldGenerator::generateHTML($field , $this->commandConfig , $this->files);
                $resource[] = $this->includeFileResource($field);
            }
        }

        $templateData = str_replace('$FILEDS$', implode(PHP_EOL.PHP_EOL, $fields) , $templateData);
        $templateData = str_replace('$INDEX_RUL$' , $this->Urls['INDEX_RUL'] , $templateData);
        $templateData = str_replace('$STORE_URL$' , $this ->Urls['SOTRE_URL'] , $templateData);
        $templateData = str_replace('$RESOURCE$' , implode(PHP_EOL.PHP_EOL, $resource) , $templateData);
        $templateData = str_replace('$ISLOADFILED$' , " ", $templateData);

        FileUtils::createFile(
            $this->viewPath . DIRECTORY_SEPARATOR,
            "create.php",
            $templateData
        );

        $this->commandData->commandComment("Create.php created: ");
    }

    private function generateIndex()
    {

        $filePath = $this->commandConfig->getViewsPath('View/index.stub');
        $templateData = $this->files->get($filePath);

        foreach ($this->Urls as $Key => $url) {
            $templateData = str_replace("$".$Key."$" , $url , $templateData);
        }
        $templateData = str_replace('$TABLE$' , $this->getTable() , $templateData);
        $templateData = str_replace('$CONTENT$' , $this->getContent() , $templateData);
        $templateData = str_replace('$PRIMARY$' , $this->commandData->getModelPrimaryKey() , $templateData);

        FileUtils::createFile(
            $this->viewPath . DIRECTORY_SEPARATOR,
            "index.php",
            $templateData
        );

        $this->commandData->commandComment("Index.php created: ");

    }

    private function getContent()
    {
        $content = [];
        $content[] = "'<tr>'";

        foreach ($this->commandData->fields as $field) {
            if (!$field->inIndex) {
                continue;
            }
            $content[] =sprintf("'<td>'+ rsData[i].%s+'</td>'",$field->name) ;
        }

        $str = "'<td>' +
                        '<a href='+ %s +'>编辑 &nbsp;&nbsp;</a>' +
                        '<a onclick=\"remove('+ rsData[i].%s +',this)\">删除</a>' +
                        '</td>' +
                        '</tr>' ";

        $content[] = sprintf($str , 'editUrl + rsData[i].'.$this->commandData->getModelPrimaryKey(), $this->commandData->getModelPrimaryKey() , $this->commandData->getModelPrimaryKey());

        return implode( '+' . infy_nl_tab(1, 8) , $content);
    }

    private function getTable()
    {
        $tableStr = [];
        foreach ($this->commandData->fields as $field) {
            if (!$field->inIndex) {
                continue;
            }
            $tableStr[] = "<th>".$field->name."</th>";
        }
        $tableStr[] = "<th>操作</th>";
        return implode(' '.infy_nl_tab(1, 12) , $tableStr);
    }

    private function includeFileResource($file)
    {

        if($file->htmlType == "file" ){
            $path = $this->commandConfig->getViewsPath('Fields/file_resource.stub');
            $templateData =  $this->files->get($path);
            $templateData = str_replace('$FILEIMGID$' , $file->name.'_imgs' , $templateData);
            $templateData = str_replace('$FILENAMEID$' , $file->name , $templateData);
            $templateData = str_replace('$UPLOADURL$' , $this->commandConfig->get('modules') . $this->commandData->modelName . '/upload' , $templateData);
            return $templateData;
        }

        if($file->htmlType == "fileOne")
        {
            $path = $this->commandConfig->getViewsPath('Fields/file_one_resource.stub');
            $templateData =  $this->files->get($path);
            $templateData = str_replace('$MODULES$', $this->commandConfig->get('modules')  , $templateData);
            $templateData = str_replace('$MODEL$', $this->commandData->modelName , $templateData);
            return $templateData;
        }


        return " ";
    }

    private function generateDirectory()
    {
        $this->files->createDirectoryIfNotExist($this->viewPath);
    }



}
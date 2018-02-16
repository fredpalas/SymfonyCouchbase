<?php

namespace Apperturedev\CouchbaseBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Apperturedev\CouchbaseBundle\Classes\CouchbaseORM;

class CouchbaseGenerateViewsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('couchbase:generate:views')
            ->setDescription('Generate views for the entity on argument Bundle:Entity For set Custom View create a Method call getCustomView')
            ->addArgument('argument', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $argument = $input->getArgument('argument');
        $option = ($input->getOption('config')!='')?$input->getOption('config'):'couchbase';
        $cb_em = $this->getCouchbase($option);
        $em = $this->getDoctrine()->getEntityManager();
        $class_name = $em->getClassMetadata($argument)->getName();
        $values = $em->getClassMetadata($argument)->getFieldNames();
        $table = $em->getClassMetadata($argument)->getTableName();
        //$output->writeln($values);
        $cb_manager = $cb_em->getEm()->manager();
        $output->writeln("Class $class_name");
        $class = new $class_name;
        $customViews=[];
        
        if(method_exists($class,'getCustomView'))
        {
            $customViews=$class->getCustomView();
        }else{
            $output->writeln("For set Custom View create a Method call getCustomView");
        }
        $view = array ( "views"=> array());
        foreach ($values as $value){
            if( !isset($customViews['views']) || !in_array($value,$customViews['exclude'],true)){
                $view["views"][$value]=[
                    "map"=> "function (doc, meta) {\n\tif(doc.$table){\n\t\temit(doc.$table.$value, doc.$table);\n\t}\n}"
                ];
                $all[] = "doc.$table.$value";
            }
        }
        if(!isset($customViews['views']) || !in_array('all',$customViews['exclude'],true)){
            $view["views"][$table."_all"]=[
                "map"=> "function (doc, meta) {\n\tif(doc.$table){\n\t\temit([".implode(",",$all)."], doc.$table);\n\t}\n}"
            ];
        }
        
        if(isset($customViews['views'])){
            foreach ($customViews['views'] as $key => $custom) {
                $view["views"][$key]=["map"=>$custom];
            }
        }
        $cb_manager->upsertDesignDocument($table,$view);
        
        $output->writeln("Views Saved");
    }
    /**
     * Shortcut to return the Doctrine Registry service.
     *
     * @return Registry
     *
     * @throws \LogicException If DoctrineBundle is not available
     */
    private Function getDoctrine(){
        $em = $this->getContainer()->get('doctrine');
        Return $em;
    }
    /**
     * Shortcut to return the Couchbase Registry service.
     *
     * @return CouchbaseORM
     *
     * @throws \LogicException If DoctrineBundle is not available
     */
    private Function getCouchbase($option = 'couchbase'){
        $em = $this->getContainer()->get($option);
        Return $em;
    }
}

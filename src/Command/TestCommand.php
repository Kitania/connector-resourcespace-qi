<?php

namespace App\Command;

use App\Qi\Qi;
use App\ResourceSpace\ResourceSpace;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TestCommand extends Command
{
    private $params;
    private $resourceSpace;
    private $qi;

    protected function configure()
    {
        $this
            ->setName('app:test')
            ->setDescription('Test');
    }

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->verbose = $input->getOption('verbose');
        $this->test();
        return 0;
    }

    private function test()
    {
        $this->resourceSpace = new ResourceSpace($this->params);
        $this->qi = new Qi($this->params);
        $searchQuery = $this->params->get('resourcespace_search_query');
        $allResources = $this->resourceSpace->getAllResources(urlencode($searchQuery));
        echo count($allResources) . ' resources total' . PHP_EOL;

        $allObjects = $this->qi->getAllObjects();
        echo count($allObjects) . ' objects total' . PHP_EOL;

        foreach ($allResources as $resourceInfo) {
            $resourceId = $resourceInfo['ref'];
            // Get this resource's metadata, but only if it has an appropriate offloadStatus
            $resourceMetadata = $this->resourceSpace->getResourceData($resourceId);
            $originalFilename = $resourceMetadata['originalfilename'];
            $inventoryNumber = $resourceMetadata['inventorynumber'];
            if(array_key_exists($inventoryNumber, $allObjects)) {
                echo 'Resource ' . $resourceId . ' has matching object ' . $allObjects[$inventoryNumber]->name . PHP_EOL;
            }
            echo $originalFilename . PHP_EOL;
        }
    }
}
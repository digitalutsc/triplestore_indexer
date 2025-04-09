<?php

namespace Drupal\triplestore_indexer\Drush\Commands;

use Drush\Commands\DrushCommands;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Queue\QueueFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;

/**
 * A Drush command to process media, node, and taxonomy term
 * deletions from a CSV file.
 */
class TriplestoreQueueCommands extends DrushCommands
{

    /**
     * Deletes media, nodes, or taxonomy terms from a CSV file.
     *
     * @command triplestore-indexer:queue_triplestore
     * @param mixed $queue_id The machine id of the queue
     * @option  file The path to the CSV file.
     * @usage   triplestore-indexer:queue_triplestore --file=path/to/file.csv
     */
    public function queueTriplestore($queue_id, $options = ['csv' => ''])
    {
        $file_path = $options['csv'];
        if (!file_exists($file_path)) {
            $this->output()->writeln("<error>File not found: $file_path</error>");
            return;
        }

        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            $this->output()->writeln(
                "<error>Failed to open file: 
            $file_path</error>"
            );
            return;
      
        }

        $triplestore_queue = Queue::load($queue_id);
        if (!$triplestore_queue) {
            $this->logger()->error(
                "Unable to load the queue {$queue_id}."
            );
            return;
        }

        $entity_ids = [];
        if ($handle !== false) {
            $row_count = 0;
            while (($data = fgetcsv($handle)) !== false) {
                // Skip header row.
                if ($row_count === 0) {
                    $row_count++;
                    continue;
                }
                if (!empty($data[0]) && !empty($data[1])) {
                    $entity_ids[] = [trim($data[0]), trim($data[1])];
                }
                $row_count++;
            }
            fclose($handle);
        } else {
            $this->logger()->error("Unable to open CSV file.");
            return;
        }


        $queued_count = 0;
        foreach ($entity_ids as $eid) {
      
            if ($eid[1] === 'delete_node') {
        
                $request = 'delete';
                $entity = Node::load($eid[0]);
                if (!$entity) {
                    $this->logger()->warning(
                        "Media with ID {$eid[0]} 
                    not found. Skipping."
                    );
                    continue;
                }

                $payload = [
                'nid' => $entity->id(),
                'type' => $entity->getEntityTypeId(),
                'action' => $request,
                'max_tries' => 5,
                'retry_delay' => 100,
                ];
  
            } else if ($eid[1] === 'delete_media') {
        
                $request = 'delete';
                $entity = Media::load($eid[0]);
                if (!$entity) {
                    $this->logger()->warning(
                        "Media with ID {$eid[0]}
                     not found. Skipping."
                    );
                    continue;
                }

                $payload = [
                'mid' => $entity->id(),
                'media_name' => $entity->getName(),
                'type' => $entity->getEntityTypeId(),
                'action' => $request,
                'max_tries' => 5,
                'retry_delay' => 100,
                ];


            } else if ($eid[1] === 'delete_taxonomy') {
        
                $request = 'delete';
                $entity = Term::load($eid[0]);
                if (!$entity) {
                    $this->logger()->warning(
                        "Media with ID {$eid[0]} 
                    not found. Skipping."
                    );
                    continue;
                }

                $payload = [
                'tid' => $entity->id(),
                'term_name' => $entity->getName(),
                'type' => $entity->getEntityTypeId(),
                'action' => $request,
                'max_tries' => 5,
                'retry_delay' => 100,
                ];  


            } else if ($eid[1] === 'index_node') {
        
                $request = 'insert';
                $entity = Node::load($eid[0]);
                if (!$entity) {
                    $this->logger()->warning(
                        "Media with ID {$eid[0]}
                     not found. Skipping."
                    );
                    continue;
                }

                $payload = [
                'nid' => $entity->id(),
                'type' => $entity->getEntityTypeId(),
                'action' => $request,
                'max_tries' => 5,
                'retry_delay' => 100,
                ];

            } else if ($eid[1] === 'index_media') {
        
                $request = 'insert';
                $entity = Media::load($eid[0]);
                if (!$entity) {
                    $this->logger()->warning(
                        "Media with ID {$eid[0]} 
                    not found. Skipping."
                    );
                    continue;
                }

                $payload = [
                'mid' => $entity->id(),
                'media_name' => $entity->getName(),
                'type' => $entity->getEntityTypeId(),
                'action' => $request,
                'max_tries' => 5,
                'retry_delay' => 100,
                ];

            } else if ($eid[1] === 'index_taxonomy') {

                $request = 'insert';
                $entity = Term::load($eid[0]);
                if (!$entity) {
                    $this->logger()->warning(
                        "Media with ID {$eid[0]}
                     not found. Skipping."
                    );
                    continue;
                }

                $payload = [
                'tid' => $entity->id(),
                'term_name' => $entity->getName(),
                'type' => $entity->getEntityTypeId(),
                'action' => $request,
                'max_tries' => 5,
                'retry_delay' => 100,
                ];  


            } else {
                $this->logger()->warning(
                    "Media with ID {$eid[0]} 
                not found. Skipping."
                );
                continue;
            }


            $job = Job::create('triplestore_index_job', $payload);
            if ($job instanceof Job) {
                $triplestore_queue->enqueueJob($job);
                $this->logger()->success("Queued job {$eid[1]} for ID {$eid[0]}.");
                $queued_count++;
            } else {
                $this->logger()->error(
                    "Failed to create job
                 {$eid[1]} for ID {$eid}."
                );
            }
        }
    }
}

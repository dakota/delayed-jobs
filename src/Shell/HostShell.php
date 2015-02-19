<?php
namespace DelayedJobs\Shell;

use Cake\Console\Shell;
use DelayedJobs\Model\Table\DelayedJobsTable;
use DelayedJobs\Process;

class HostShell extends Shell
{
    public $Lock;
    public $modelClass = 'DelayedJobs.DelayedJobs';
    protected $_worker_id;
    protected $_worker_name;

    public function main()
    {
        $this->_worker_name = 'worker1';

        if (isset($this->args[0])) {
            $this->_worker_name = $this->args[0];
        }

        //TODO: Migrate lock component into simple library
//        $this->Lock = new LockComponent();
//        $this->Lock->lock('DelayedJobs.HostShell.main.' . $worker_name);

        $this->_worker_id = $this->_worker_name . ' - ' . php_uname('a');

        /*
         * Get Next Job
         * Get Exclusive Lock on Job
         * Fire Worker
         * Worker fires job
         * Worker monitors the exection time
         */

        $job_pids = [];

        $max_allowed_jobs = 1;

        //## Need to make sure that any running jobs for this host is in the array job_pids

        $running_jobs = $this->DelayedJobs->getRunningByHost($this->_worker_id);

        foreach ($running_jobs as $running_job) {
            $job_pids[$running_job->id] = [
                'pid' => $running_job->pid,
            ];
        }

        $this->out('<info>Started up</info>', 1, Shell::VERBOSE);
        while (true) {
            if (count($job_pids) < $max_allowed_jobs) {
                $this->_executeJob();
            } else {
                $this->out('<info>Maximum allowed jobs running</info>', 1, Shell::VERBOSE);
            }

            //## Check Status of Fired Jobs
            foreach ($job_pids as $job_id => $running_jobs) {
                $job = $this->DelayedJobs->get($job_id);

                $status = new Process();
                $status->setPid($running_jobs['pid']);
                if (!$status->status()) {
                    //## Make sure that this job is not marked as running
                    if ($job->status === DelayedJobsTable::STATUS_BUSY) {
                        $this->DelayedJobs->failed(
                            $job,
                            'Job not running, but db said it is, could be a runtime error'
                        );
                    }
                    unset($job_pids[$job_id]);
                    $this->out('<info>Job: ' . $job_id . ' no longer running</info>', 1, Shell::VERBOSE);
                } else {
                    //## Check if job has not reached it max exec time
                    $busy_time = time() - $running_jobs['start_time'];

                    if ($busy_time > $running_jobs['max_execution_time']) {
                        $this->out('<info>Job: ' . $job_id . ' Running too long, need to kill it</info>', 1, Shell::VERBOSE);
                        $status->stop();

                        $this->DelayedJobs->failed($job, 'Job ran too long, killed');
                    } else {
                        $this->out(
                            '<info>Job: ' . $job_id . ' still running: ' . $busy_time . '</info>',
                            1,
                            Shell::VERBOSE
                        );
                    }
                }
            }

            //## Sleep so that the system can rest
            sleep(2);
        }
    }

    protected function _executeJob() {
        $job = $this->DelayedJobs->getOpenJob($this->_worker_id);

        if ($job) {
            $this->out('<info>Got a new job</info>', 1, Shell::VERBOSE);
            if (!isset($job_pids[$job->id])) {
                $options = $job->options;

                if (!isset($options['max_execution_time'])) {
                    $options['max_execution_time'] = 25 * 60;
                }

                $path = ROOT . '/bin/cake DelayedJobs.Worker ' . $job->id;
                $p = new Process($path);

                $pid = $p->getPid();

                $this->DelayedJobs->setPid($job, $pid);

                $job_pids[$job->id] = [
                    'pid' => $pid,
                    'start_time' => time(),
                    'max_execution_time' => $options['max_execution_time'],
                ];
                $this->out('<info>Job runner forked</info>', 1, Shell::VERBOSE);
            }
        }
    }

    public function getOptionParser()
    {
        $options = parent::getOptionParser();

        $options
            ->addArgument('workerName', [
                'help' => 'Custom worker name to use',
            ]);

        return $options;
    }

}
<?php

namespace DelayedJobs\Controller;

use App\Controller\AppController;
use Cake\I18n\Time;
use DelayedJobs\DelayedJob\DelayedJobInterface;
use DelayedJobs\DelayedJob\EnqueueTrait;
use DelayedJobs\Model\Table\DelayedJobsTable;

/**
 * Class DelayedJobsController
 * @package DelayedJobs\Controller
 * @property \DelayedJobs\Model\Table\DelayedJobsTable $DelayedJobs
 */
class DelayedJobsController extends AppController 
{
    use EnqueueTrait;

    /**
     * @return void
     * @codeCoverageIgnore
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Crud.Crud', [
            'actions' => [
                'Crud.Index',
                'Crud.View',
            ]
        ]);

        if (!$this->components()->has('Flash')) {
            $this->loadComponent('Flash');
        }
    }

    /**
     * @return mixed
     */
    public function index()
    {
        $jobs_per_second = $this->DelayedJobs->jobsPerSecond();

        $this->set(compact('jobs_per_second'));

        return $this->Crud->execute();
    }

    public function view()
    {
        return $this->Crud->execute();
    }

    /**
     * Runs a delayed job
     *
     * @param int $id Job ID.
     * @return \Cake\Network\Response|void
     */
    public function run($id)
    {
        $this->layout = false;
        $this->autoRender = false;

        $job = $this->DelayedJobs->get($id);

        if ($job->status === DelayedJobsTable::STATUS_SUCCESS) {
            $this->Flash->error("Job Already Completed");
            return $this->redirect(['action' => 'index']);
        }

        $response = $job->execute();

        $this->Flash->message("Job Completed: " . $response);
        return $this->redirect(['action' => 'index']);
    }

    /**
     * @return \Cake\Network\Response|void
     */
    public function inject()
    {
        $number_jobs = $this->request->query('count') ?: 1;

        for ($i = 0; $i < $number_jobs; $i++) {
            $this->enqueue('DelayedJobs.Test', [
                'type' => $this->request->query('type') ?: 'success'
            ], [
                'priority' => rand(1, 9) * 10 + 100,
                'run_at' => new Time('+10 seconds')
            ]);
        }

        return $this->redirect([
            'action' => 'index'
        ]);
    }
}

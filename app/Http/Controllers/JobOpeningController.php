<?php

namespace App\Http\Controllers;

use App\Models\JobOpening;
use Illuminate\Http\Request;
use Validator;

class JobOpeningController extends Controller
{

    /*
     * HARDCODED WORKFLOW
     * This can be split in 3 entities: workflow, state, action
     * Each workflow has many states and many actions
     * Each state has a name and one or more possible actions
     * Each action has a name, a required state, a required roles list and a new state value that will be assigned to the job opening after the action is performed
     * In a more advanced version, a client can be able to create different workflows by creating and assigning states and actions.
     * What I would add:
     * 1.Action -> requiredState as an array (so an action can be performed from different states)
     * 2.State -> roleVisibility as an array (which roles can see this state)
     * All that can be represented in a nice UI and can be easily configured by the client/app admin without the need of a software developer.
     */
    public function workflow($json = true){

        $states = [];
        $actions = [];
        $workflow = new \stdClass();

        //Define states
        $draftState = new \stdClass();
        $draftState->name = "draft";
        $draftState->possibleActions = ['post_draft','cancel_draft'];
        $states[$draftState->name] = $draftState;

        $postedState = new \stdClass();
        $postedState->name = "posted";
        $postedState->possibleActions = ['accept_post','retract_post'];
        $states[$postedState->name] = $postedState;

        $pendingState = new \stdClass();
        $pendingState->name = "pending";
        $pendingState->possibleActions = ['propose_pending'];
        $states[$pendingState->name] = $pendingState;

        $proposedState = new \stdClass();
        $proposedState->name = "proposed";
        $proposedState->possibleActions = ['accept_proposed', 'reject_proposed'];
        $states[$proposedState->name] = $proposedState;

        $contractedState = new \stdClass();
        $contractedState->name = "contracted";
        $contractedState->possibleActions = [];
        $states[$contractedState->name] = $contractedState;

        $canceledState = new \stdClass();
        $canceledState->name = "canceled";
        $canceledState->possibleActions = [];
        $states[$canceledState->name] = $canceledState;


        //Defined actions
        $postDraftAction = new \stdClass();
        $postDraftAction->name = "post_draft";
        $postDraftAction->requiredState = "draft";
        $postDraftAction->newState = "posted";
        $postDraftAction->requiredRoles = ['client'];
        $actions[$postDraftAction->name] = $postDraftAction;

        $cancelDraftAction = new \stdClass();
        $cancelDraftAction->name = "cancel_draft";
        $cancelDraftAction->requiredState = "draft";
        $cancelDraftAction->newState = "canceled";
        $cancelDraftAction->requiredRoles = ['client'];
        $actions[$cancelDraftAction->name] = $cancelDraftAction;


        $acceptPostAction = new \stdClass();
        $acceptPostAction->name = "accept_post";
        $acceptPostAction->requiredState = "posted";
        $acceptPostAction->newState = "pending";
        $acceptPostAction->requiredRoles = ['provider'];
        $actions[$acceptPostAction->name] = $acceptPostAction;

        $retractPostAction = new \stdClass();
        $retractPostAction->name = "retract_post";
        $retractPostAction->requiredState = "posted";
        $retractPostAction->newState = "canceled";
        $retractPostAction->requiredRoles = ['client'];
        $actions[$retractPostAction->name] = $retractPostAction;

        $proposePendingAction = new \stdClass();
        $proposePendingAction->name = "propose_pending";
        $proposePendingAction->requiredState = "pending";
        $proposePendingAction->newState = "proposed";
        $proposePendingAction->requiredRoles = ['provider'];
        $actions[$proposePendingAction->name] = $proposePendingAction;

        $acceptProposedAction = new \stdClass();
        $acceptProposedAction->name = "accept_proposed";
        $acceptProposedAction->requiredState = "proposed";
        $acceptProposedAction->newState = "contracted";
        $acceptProposedAction->requiredRoles = ['client'];
        $actions[$acceptProposedAction->name] = $acceptProposedAction;

        $rejectProposedAction = new \stdClass();
        $rejectProposedAction->name = "reject_proposed";
        $rejectProposedAction->requiredState = "proposed";
        $rejectProposedAction->newState = "canceled";
        $rejectProposedAction->requiredRoles = ['client'];
        $actions[$rejectProposedAction->name] = $rejectProposedAction;

        $workflow->states = $states;
        $workflow->actions = $actions;

        if($json) return response()->json($workflow);
        return $workflow;
    }

    public function indexClient()
    {
        $jobOpenings = JobOpening::all();

        if (!count($jobOpenings)) return response()->json(['message' => 'There are 0 job openings at the moment', 'job_openings' => $jobOpenings]);

        return response()->json(['message' => "These are the current job openings", 'job_openings' => $jobOpenings]);
    }

    public function indexProvider()
    {
        $jobOpenings = JobOpening::where([['state','!=', 'draft'],['state', '!=', 'canceled']])->get();

        if (!count($jobOpenings)) return response()->json(['message' => 'There are 0 job openings at the moment', 'job_openings' => $jobOpenings]);

        return response()->json(['message' => "These are the current job openings", 'job_openings' => $jobOpenings]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|min:6',
            'content' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $jobOpening = JobOpening::create([
            'title' => $request->title,
            'content' => $request->content,
            'state' => 'draft', //initial state
        ]);

        return response()->json([
            'message' => 'Job Opening succesfully created!',
            'job_opening' => $jobOpening
        ]);
    }

    public function makeAction(Request $request){
        $validator = Validator::make($request->all(), [
            'action' => 'required|string',
            'job_opening' => 'required|exists:job_openings,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $workflow = $this->workflow(false);

        //1.Check if action exists
        $currentAction = $workflow->actions[$request->action];
        if(!$currentAction)
            return response()->json(['message' => 'Action not found'],'400');

        //2.Check if role has permission to make that action
        if(array_search($request->user()->role,$currentAction->requiredRoles) === false)
            return response()->json(['message' => 'This role cannot make that change'],'400');

        //3.Check if job opening's current state is action's required state
        $jobOpening = JobOpening::findOrFail($request->job_opening);
        if($jobOpening->state != $currentAction->requiredState)
            return response()->json(['message' => 'This action cannot be performed in this state'],'400');

        $jobOpening->state = $currentAction->newState;
        $jobOpening->save();

        return response()->json(['message' => 'Action succesfully performed!', 'job_opening' => $jobOpening]);
    }
}

<?php
namespace Laraquick\Controllers\Traits\Crud;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\Model;

use DB;
use Log;

/**
 * Methods for updating a resource
 *
 */
trait Update
{

    /**
     * Create a 404 not found error response
     *
     * @return Response
     */
    abstract protected function notFoundError();

    /**
     * Error message for when an update action fails
     *
     * @return Response
     */
    abstract protected function updateFailedError();
    
    /**
     * The model to use in the update method.
     *
     * @return mixed
     */
    abstract protected function updateModel();

    /**
     * Called after validation but before update method is called
     *
     * @param array $data
     * @return mixed The response to send or null
     */
    protected function beforeUpdate(array &$data)
    {
    }

    /**
     * Called when an error occurs in a update operation
     *
     * @return void
     */
    protected function rollbackUpdate()
    {
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();
        if ($resp = $this->checkRequestData($data, $this->validationRules($data, $id)))
            return $resp;

        $model = $this->updateModel();

        $item = is_object($model)
            ? $model->find($id)
            : $model::find($id);
        if (!$item) return $this->notFoundError();

        try {
            DB::beginTransaction();
            if ($resp = $this->beforeUpdate($data)) return $resp;

            $result = $item->update($data);

            if (!$result) {
                throw new \Exception('Update method returned falsable', null, 500);
            }
        }
        catch (\Exception $ex) {
            Log::error('Update: ' . $ex->getMessage(), [$data]);
            $this->rollbackUpdate();
            DB::rollback();
            return $this->updateFailedError();
        }

        if ($resp = $this->beforeUpdateResponse($item)) {
            return $resp;
        }
        DB::commit();
        return $this->updateResponse($item);
    }

    /**
     * Called on success but before sending the response
     *
     * @param mixed $data
     * @return mixed The response to send or null
     */
    protected function beforeUpdateResponse(Model &$data)
    {
    }

    /**
     * Called for the response to method update()
     *
     * @param Model $data
     * @return Response|array
     */
    abstract protected function updateResponse(Model $data);
    
}
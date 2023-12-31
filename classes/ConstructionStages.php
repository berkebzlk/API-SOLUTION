<?php

require_once 'Core/Validator.php';
require_once 'ConstructionStagesService.php';

/**
 * Class ConstructionStages
 *
 * Controller class for handling construction stages data.
 */
class ConstructionStages
{
    /**
     * Get all construction stages.
     *
     * @return mixed JSON response containing all construction stages.
     */
    public function getAll()
    {
        try {
            $service = new ConstructionStagesService();
            $response = $service->getAll();
            return json_response($response);
        } catch (Exception $e) {
            return json_response(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get a single construction stage by ID.
     *
     * @param int $id The ID of the construction stage.
     * @return mixed JSON response containing the construction stage.
     */
    public function getSingle($id)
    {
        try {
            $service = new ConstructionStagesService();
            $response = $service->getSingle($id);
            return json_response($response);
        } catch (Exception $e) {
            return json_response(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a new construction stage.
     *
     * @param ConstructionStagesCreate $data The data for creating the construction stage.
     * @return mixed JSON response containing the created construction stage.
     */
    public function post(ConstructionStagesCreate $data)
    {
        try {
            $service = new ConstructionStagesService();
            $created = $service->create($data);
            $response = $this->getSingle($created);
            return json_response($response);
        } catch (Exception $e) {
            return json_response(['error' => $e->getMessage()]);
        }
    }

    /**
     * Patch (partially update) a construction stage.
     *
     * @param ConstructionStagesCreate $data The JSON data for updating the construction stage.
     * @param int $id The ID of the construction stage to update.
     * @return mixed JSON response containing the updated construction stage.
     */
    public function patch(ConstructionStagesCreate $data, $id)
    {
        try {
            $service = new ConstructionStagesService();
            $updated = $service->patch($data, $id);
            $response = $this->getSingle($updated);
            return json_response($response);
        } catch (Exception $e) {
            return json_response(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete a construction stage by ID.
     *
     * @param int $id The ID of the construction stage to delete.
     * @return mixed JSON response containing a success message on successful deletion.
     */
    public function delete($id)
    {
        try {
            $service = new ConstructionStagesService();
            $response = $service->delete($id);
            return json_response(['message' => $response]);
        } catch (Exception $e) {
            return json_response(['error' => $e->getMessage()]);
        }
    }
}

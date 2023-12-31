<?php

/**
 * Class ConstructionStagesService
 *
 * Service class for handling construction stages data.
 */
class ConstructionStagesService
{

    /**
     * @var PDO Database connection instance.
     */
    private $db;

    /**
     * @var array Validation rules for construction stage data.
     */
    private $rules = [
        'name' => 'required|string|max:255',
        'startDate' => 'required|dateFormat',
        'endDate' => 'dateFormat|after:startDate|nullable',
        'duration' => 'skip',
        'durationUnit' => 'in:HOURS,DAYS,WEEKS|nullable',
        'color' => 'hexColor|nullable',
        'externalId' => 'string|max:255|nullable',
        'status' => 'default:NEW|required|in:NEW,PLANNED,DELETED'
    ];

    /**
     * ConstructionStagesService constructor.
     */
    public function __construct()
    {
        $this->db = Api::getDb();
    }

    /**
     * Get all construction stages.
     *
     * @return mixed JSON response containing all construction stages.
     */
    public function getAll()
    {
        $stmt = $this->db->prepare("
		SELECT
		ID as id,
				name,
				strftime('%Y-%m-%dT%H:%M:%SZ', start_date) as startDate,
				strftime('%Y-%m-%dT%H:%M:%SZ', end_date) as endDate,
				duration,
				durationUnit,
				color,
				externalId,
				status
			FROM construction_stages
		");
        // WHERE status is not 'DELETED'

        $stmt->execute();

        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return json_response($response);
    }

    /**
     * Get a single construction stage by ID.
     *
     * @param int $id The ID of the construction stage.
     * @return mixed JSON response containing the construction stage.
     * @throws Exception If there is no construction stage with the specified ID.
     */
    public function getSingle($id)
    {
        $sql = "SELECT
				ID as id,
				name,
				strftime('%Y-%m-%dT%H:%M:%SZ', start_date) as startDate,
				strftime('%Y-%m-%dT%H:%M:%SZ', end_date) as endDate,
				duration,
				durationUnit,
				color,
				externalId,
				status
			FROM construction_stages
			WHERE ID = :id
		";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);

            $response = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$response) {
                throw new Exception('There is no construction stage with id: ' . $id);
            }

            return json_response($response);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Create a new construction stage.
     *
     * @param ConstructionStagesCreate $data The data for creating the construction stage.
     * @return int The ID of the created construction stage.
     * @throws Exception If there is an error during the creation process.
     */
    public function create(ConstructionStagesCreate $data)
    {
        try {
            $validator = new Validator($data, $this->rules);

            if (!$validator->validate()) {
                $errors = $validator->errors();
                $errorString = implode("\n", array_map(
                    function ($fieldErrors, $fieldName) {
                        return $fieldName . ': ' . implode(', ', $fieldErrors);
                    },
                    $errors,
                    array_keys($errors)
                ));
                throw new Exception($errorString);
            }

            $duration = "";
            if ($data->endDate == null || $data->endDate == "") {
                //
            } else {
                $startDate = new DateTime($data->startDate);
                $endDate = new DateTime($data->endDate);

                $durationUnit = $this->originalOrdefaultDurationUnit($data->durationUnit);

                $durationUnit = strtolower($durationUnit);
                $durationUnit = substr($durationUnit, 0, 1);

                $duration = $this->calculateDuration($durationUnit, $startDate, $endDate);

                $data->durationUnit = $data->durationUnit ?: 'DAYS';
            }

            $stmt = $this->db->prepare("
				INSERT INTO construction_stages
					(name, start_date, end_date, duration, durationUnit, color, externalId, status)
					VALUES (:name, :start_date, :end_date, :duration, :durationUnit, :color, :externalId, :status)
				");

            $data->status = $data->status ?: 'NEW';

            $success = $stmt->execute([
                'name' => $data->name,
                'start_date' => $data->startDate,
                'end_date' => $data->endDate,
                'duration' => $duration,
                'durationUnit' => $data->durationUnit,
                'color' => $data->color,
                'externalId' => $data->externalId,
                'status' => $data->status,
            ]);

            if (!$success) {
                throw new Exception('Something went wrong while creating construction stage');
            }

            return $this->db->lastInsertId();
        } catch (Exception $e) {
            json_response(['error' => $e->getMessage()]);
        }
    }

    /**
     * Calculate duration based on the duration unit.
     *
     * @param string $durationUnit The unit of duration (HOURS, DAYS, WEEKS).
     * @param DateTime $startDate The start date of the construction stage.
     * @param DateTime $endDate The end date of the construction stage.
     * @return float The calculated duration.
     * @throws Exception If an invalid duration unit is provided.
     */
    private function calculateDuration($durationUnit, $startDate, $endDate)
    {
        switch ($durationUnit) {
            case 'h':
                $duration = $startDate->diff($endDate)->h + $startDate->diff($endDate)->days * 24;
                break;
            case 'd':
                $duration = $startDate->diff($endDate)->days;
                break;
            case 'w':
                $duration = ($startDate->diff($endDate)->days) / 7;
                break;
            default:
                throw new Exception('INVALID_UNIT');
        }
        return $duration;
    }

    /**
     * Get the original or default duration unit.
     *
     * @param string|null $durationUnit The duration unit.
     * @return string The original or default duration unit.
     */
    private function originalOrdefaultDurationUnit($durationUnit)
    {
        if ($durationUnit == "" || $durationUnit == null) {
            return 'DAYS';
        }

        return $durationUnit;
    }

    /**
     * Patch (partially update) a construction stage.
     *
     * @param ConstructionStagesCreate $jsonData The JSON data for updating the construction stage.
     * @param int $id The ID of the construction stage to update.
     * @return mixed JSON response containing the updated construction stage.
     * @throws Exception If there is an error during the update process.
     */
    public function patch(ConstructionStagesCreate $jsonData, $id)
    {
        try {
            $data = (array) $jsonData;

            if (!is_array($data)) {
                throw new Exception('Invalid JSON data');
            }

            $success = $this->updateColumns($data, $id);

            if (!$success) {
                throw new Exception('Something went wrong while updating constuction stage with id: ' . $id);
            }

            return $this->getSingle($id);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Delete a construction stage by ID.
     *
     * @param int $id The ID of the construction stage to delete.
     * @return string Success message on successful deletion.
     * @throws Exception If there is an error during the deletion process.
     */
    public function delete($id)
    {
        try {
            $this->findConstructionStageById($id);

            $stmt = $this->db->prepare("UPDATE construction_stages SET status = :status WHERE id = :id");

            $stmt->execute([
                'status' => 'DELETED',
                'id' => $id
            ]);

            return "Construction Stage with id: {$id} deleted successfully!";
        } catch (Exception $e) {
            json_response(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update specified columns for a construction stage.
     *
     * @param array $data The data to update for the construction stage.
     * @param int $id The ID of the construction stage to update.
     * @return bool True if the update is successful, false otherwise.
     * @throws Exception If there is an error during the update process.
     */
    private function updateColumns($data, $id)
    {
        try {
            $setStatements = [];
            $params = ['id' => $id];

            foreach ($data as $columnName => $value) {
                if ($value == null && $value == '') continue;

                $setStatements[] = "$columnName = :$columnName";
                $params[$columnName] = $value;
            }

            $updateSql = "UPDATE construction_stages SET " . implode(', ', $setStatements) . " WHERE id = :id";
            $stmt = $this->db->prepare($updateSql);

            $success = $stmt->execute($params);
            return $success;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Find a construction stage by ID.
     *
     * @param int $id The ID of the construction stage to find.
     * @return mixed The construction stage data.
     * @throws Exception If there is no construction stage with the specified ID.
     */
    private function findConstructionStageById($id)
    {
        try {
            $sql = "SELECT * FROM construction_stages WHERE id = :id AND status is not 'DELETED'";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id' => $id
            ]);

            $constructionStage = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$constructionStage) throw new Exception('There is no constraction stage with id: ' . $id);

            return $constructionStage;
        } catch (Exception $e) {
            throw $e;
        }
    }
}

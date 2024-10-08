<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Support\Facades\Gate;

trait HasPolicies
{

    /**
     * Qualifies the collection query to allow you to add params vai the policy
     * ie to limit to a specific user id mapping.
     */
    protected function qualifyCollectionQuery(): void
    {
        $user = auth($this->guard)->user();
        $modelPolicy = Gate::getPolicyFor($this->model());

        if ($modelPolicy && method_exists($modelPolicy, 'qualifyCollectionQueryWithUser')) {
            $modelPolicy->qualifyCollectionQueryWithUser($user, $this->getBuilder());
        }
    }

    /**
     * Qualifies the collection query to allow you to add params vai the policy
     * ie to limit to a specific user id mapping
     * This may be overkill but could be usedfull ?
     */
    protected function qualifyItemQuery(): void
    {
        $user = auth($this->guard)->user();
        $modelPolicy = Gate::getPolicyFor($this->model());

        if ($modelPolicy && method_exists($modelPolicy, 'qualifyItemQueryWithUser')) {
            $modelPolicy->qualifyItemQueryWithUser($user, $this->getBuilder());
        }
    }

    /**
     * Allows you to massage the data when creating a new record.
     *
     * @param array $data
     *
     * @return array
     */
    protected function qualifyStoreQuery(array $data): array
    {
        $user = auth($this->guard)->user();
        $modelPolicy = Gate::getPolicyFor($this->model());

        if ($modelPolicy && method_exists($modelPolicy, 'qualifyStoreDataWithUser')) {
            $data = $modelPolicy->qualifyStoreDataWithUser($user, $data);
        }

        return $data;
    }

    /**
     * Allows you to massage the data when updating an existing record.
     *
     * @param array $data
     *
     * @return array
     */
    protected function qualifyUpdateQuery(array $data): array
    {
        $user = auth($this->guard)->user();
        $modelPolicy = Gate::getPolicyFor($this->model());

        if ($modelPolicy && method_exists($modelPolicy, 'qualifyUpdateDataWithUser')) {
            $data = $modelPolicy->qualifyUpdateDataWithUser($user, $data);
        }

        return $data;
    }

    /**
     * Checks if the user has access to an ability.
     *
     * @param string $ability
     * @param mixed $arguments
     *
     * @return bool
     */
    protected function authoriseUserAction(string $ability, $arguments = null, bool $excludeMissing = false): bool
    {
        if (! $this->testUserPolicyAction($ability, $arguments, $excludeMissing)) {
            /** @scrutinizer ignore-call */
            $this->errorUnauthorized();
        }

        return true;
    }

    /**
     * checks if the user can access via gate policies.
     *
     * @param string $ability
     * @param mixed $arguments
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     *
     * @return bool
     */
    protected function testUserPolicyAction(string $ability, $arguments = null, bool $excludeMissing = false): bool
    {

        // If no arguments are specified, set it to the controller's model (default)
        if ($arguments === null) {
            $arguments = $this->model();
        }

        // Get policy for model
        if (is_array($arguments)) {
            $model = reset($arguments);
        } else {
            $model = $arguments;
        }

        $modelPolicy = Gate::getPolicyFor($model);

        // If no policy exists for this model, then there's nothing to check
        if (is_null($modelPolicy) || ($excludeMissing && ! method_exists($modelPolicy, $ability))) {
            return true;
        }

        $user = auth($this->guard)->user();

        /* @scrutinizer ignore-call */
        $this->authorizeForUser($user, $ability, $model);

        return true;
    }
}

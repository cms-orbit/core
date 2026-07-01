<?php

namespace CmsOrbit\Core\Screen\Concerns;

use CmsOrbit\Core\Screen\Repository;
use Illuminate\Support\Facades\Crypt;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait InteractsWithEncryptedState
{
    /**
     * This method extracts the state from a request parameter.
     * If the '_state' parameter is missing, an empty Repository object is returned.
     * Otherwise, the state is extracted from the encrypted '_state' parameter, deserialized and returned.
     *
     *
     * @return Repository - The extracted state.
     *
     * @throws ContainerExceptionInterface - If the container cannot provide the dependency injection for a class.
     * @throws NotFoundExceptionInterface - If the container cannot find a required dependency injection for a class.
     */
    protected function extractState(?string $state = null): Repository
    {
        if ($state === null) {
            $state = request()->input('_state', session()->get('_state'));
        }

        // Check if the '_state' parameter is present
        if ($state === null) {
            // Return an empty Repository object
            return new Repository;
        }

        // deserialize '_state' parameter
        $decoded = Crypt::decrypt($state);

        return new Repository(get_object_vars($decoded));
    }

    /**
     * Serializes the current state of the screen into a string.
     *
     *
     * @return string The serialized state.
     *
     * @throws PhpVersionNotSupportedException If the PHP version is not supported for serialization.
     */
    protected function serializableState(): string
    {
        return Crypt::encrypt($this);
    }
}

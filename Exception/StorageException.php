<?php

declare(strict_types=1);

/**
 * User: Luigi Cardamone <luigi.cardamone@ped.technology>
 * Date: 11/03/19
 * Time: 12:30.
 */

namespace App\Exception;

use Exception;

class StorageException extends Exception
{
    /**
     * Exception Type.
     *
     * @var string
     */
    private $type;

    /**
     * Parameters and Translation parameters.
     *
     * @var array
     */
    private $parameters;

    /**
     * TranslatableException constructor.
     *
     * @param string $message    Message or translation key
     * @param string $type       Unique identifier for an error
     * @param array  $parameters Parameters and Translation parameters
     */
    public function __construct($message, $type, $parameters = [])
    {
        parent::__construct($message);

        $this->type = $type;
        $this->parameters = $parameters;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}

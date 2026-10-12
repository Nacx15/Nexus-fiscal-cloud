<?php

namespace App\Infrastructure\Cfdi;

use CfdiUtils\CfdiCreator40;
use CfdiUtils\XmlResolver\XmlResolver;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class CfdiCreatorFactory
{
    public function make(
        array $attributes = []
    ): CfdiCreator40 {
        $resourcesPath =
            config('cfdi.resources_path');

        if (
            !is_string($resourcesPath) ||
            $resourcesPath === ''
        ) {
            throw new RuntimeException(
                'CFDI resources path is not configured.'
            );
        }

        File::ensureDirectoryExists(
            $resourcesPath
        );

        $resolver =
            new XmlResolver(
                $resourcesPath
            );

        $creator =
            new CfdiCreator40(
                $attributes
            );

        $creator->setXmlResolver(
            $resolver
        );

        return $creator;
    }
}

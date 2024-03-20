<?php

declare(strict_types=1);

namespace Doctrine\REST\Client\URLGenerator;

use function array_key_exists;
use function http_build_query;
use function implode;
use function is_array;

use const null;

class StandardURLGenerator extends AbstractURLGenerator
{
    public function generate(array $options)
    {
        $id = $options['id'] ?? null;
        $action = $options['action'] ?? null;

        $queryStr = '';

        if (array_key_exists('parameters', $options) && is_array($options['parameters'])) {
            $parameters = $options['parameters'];

            foreach ($this->_entityConfiguration->getProperties() as $field) {
                unset($parameters[$field]);
            }

            if ($parameters) {
                $queryStr = '?' . http_build_query($parameters);
            }
        }

        $suffix = $this->_entityConfiguration->getAppendSuffix()
            ? '.' . $this->_entityConfiguration->getResponseType()
            : ''
        ;

        $urlParts = [];

        if ($id) {
            $urlParts[] = $this->_entityConfiguration->getName();

            if (null === $action) {
                $urlParts[] = $id . $suffix;
            } else {
                $urlParts[] = $id;
                $urlParts[] = $action . $suffix;
            }
        } else {
            if (null === $action) {
                $urlParts[] = $this->_entityConfiguration->getName() . $suffix;
            } else {
                $urlParts[] = $this->_entityConfiguration->getName();
                $urlParts[] = $action . $suffix;
            }
        }

        $url = $this->_entityConfiguration->getUrl() . '/' . implode('/', $urlParts) . $queryStr;

        return $url;
    }
}

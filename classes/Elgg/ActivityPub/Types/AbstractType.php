<?php

namespace Elgg\ActivityPub\Types;

use DateTime;
use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\ActivityPub\Helpers\ExportableInterface;
use ReflectionClass;

abstract class AbstractType implements ExportableInterface
{
    #[ExportProperty]
    protected string $type;

    protected array $contexts = [
        ActivityPubActivity::CONTEXT_URL,
    ];

    public function getContextExport(): array
    {
        return [
            '@context' => count($this->contexts) === 1 ? $this->contexts[0] : $this->contexts
        ];
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function export(array $extras = []): array
    {
        $export = [];

        $reflection = new ReflectionClass($this);

        // Get the parent class reflection if it exists
        if ($parentClass = $reflection->getParentClass()) {
            // Export properties from the parent class first
            $parentProperties = $parentClass->getProperties();
            foreach ($parentProperties as $property) {
                $attributes = $property->getAttributes();
                foreach ($attributes as $attribute) {
                    if ($attribute->getName() === ExportProperty::class && isset($this->{$property->getName()})) {
                        $value = $this->{$property->getName()};
                        if ($value instanceof ExportableInterface) {
                            $value = $value->export();
                        }
                        if (is_array($value)) {
                            foreach ($value as $k => $v) {
                                if ($v instanceof ExportableInterface) {
                                    $value[$k] = $v->export();
                                }
                            }
                        }
                        $export[$property->getName()] = $value;
                    }
                }
            }
        }

        // Export properties from the current class
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $attributes = $property->getAttributes();

            foreach ($attributes as $attribute) {
                if ($attribute->getName() === ExportProperty::class && isset($this->{$property->getName()})) {
                    $value = $this->{$property->getName()};

                    if ($value instanceof ExportableInterface) {
                        $value = $value->export();
                    }

                    if (is_array($value)) {
                        foreach ($value as $k => $v) {
                            if ($v instanceof ExportableInterface) {
                                $value[$k] = $v->export();
                            }
                        }
                    }

                    if ($value instanceof DateTime) {
                        $value = $value->format('c');
                    }

                    $export[$property->getName()] = $value;
                }
            }
        }

        return $export;
    }
}

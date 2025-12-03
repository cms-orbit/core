<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth;

/**
 * Permission
 * 
 * 권한 정보를 담는 Value Object
 */
class Permission
{
    /**
     * Constructor
     *
     * @param string $slug
     * @param string $name
     * @param string $group
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $group
    ) {
    }

    /**
     * To array
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'group' => $this->group,
        ];
    }
}


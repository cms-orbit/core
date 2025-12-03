<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Models\Concerns;

/**
 * Has Counters Trait
 * 
 * 조회수, 추천수, 댓글수 등의 카운터 관리
 */
trait HasCounters
{
    /**
     * Record a view
     *
     * @return static
     */
    public function recordView(): static
    {
        $column = $this->getReadCountColumn();
        
        $this->increment($column);
        
        return $this;
    }

    /**
     * Increment assent count
     *
     * @return static
     */
    public function incrementAssent(): static
    {
        $column = $this->getAssentCountColumn();
        
        $this->increment($column);
        
        return $this;
    }

    /**
     * Decrement assent count
     *
     * @return static
     */
    public function decrementAssent(): static
    {
        $column = $this->getAssentCountColumn();
        
        $this->decrement($column);
        
        return $this;
    }

    /**
     * Increment dissent count
     *
     * @return static
     */
    public function incrementDissent(): static
    {
        $column = $this->getDissentCountColumn();
        
        $this->increment($column);
        
        return $this;
    }

    /**
     * Decrement dissent count
     *
     * @return static
     */
    public function decrementDissent(): static
    {
        $column = $this->getDissentCountColumn();
        
        $this->decrement($column);
        
        return $this;
    }

    /**
     * Increment comment count
     *
     * @return static
     */
    public function incrementComments(): static
    {
        $this->increment('comment_count');
        
        return $this;
    }

    /**
     * Decrement comment count
     *
     * @return static
     */
    public function decrementComments(): static
    {
        $this->decrement('comment_count');
        
        return $this;
    }

    /**
     * Get the read count column name
     *
     * @return string
     */
    public function getReadCountColumn(): string
    {
        return 'read_count';
    }

    /**
     * Get the assent count column name
     *
     * @return string
     */
    public function getAssentCountColumn(): string
    {
        return 'assent_count';
    }

    /**
     * Get the dissent count column name
     *
     * @return string
     */
    public function getDissentCountColumn(): string
    {
        return 'dissent_count';
    }
}


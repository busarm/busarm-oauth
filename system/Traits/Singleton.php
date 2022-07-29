<?php

namespace System\Traits;

/**
 * Created by VSCODE.
 * User: Samuel
 * Date: 29/7/2022
 * Time: 11:21 AM
 */
trait Singleton
{
    public function setInstance() {
        return app()->singletons[static::class] = $this;
    }
    
    /**
     * @return static
     */
    public static function getInstance(...$params): static {
        if (isset(app()->singletons[static::class])) {
            return app()->singletons[static::class];
        }  else {
            return app()->singletons[static::class] = new static(...$params);
        } 
    }
}
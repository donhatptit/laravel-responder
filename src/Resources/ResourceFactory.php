<?php

namespace Flugg\Responder\Resources;

use Flugg\Responder\Contracts\Resources\ResourceFactory as ResourceFactoryContract;
use Flugg\Responder\Contracts\Transformers\TransformerResolver;
use Flugg\Responder\Transformers\TransformerManager;
use League\Fractal\Resource\Collection as CollectionResource;
use League\Fractal\Resource\Item as ItemResource;
use League\Fractal\Resource\NullResource;
use League\Fractal\Resource\ResourceInterface;

/**
 * This class is responsible for making Fractal resources from a variety of data types.
 *
 * @package flugger/laravel-responder
 * @author  Alexander Tømmerås <flugged@gmail.com>
 * @license The MIT License
 */
class ResourceFactory implements ResourceFactoryContract
{
    /**
     * A service class, used to normalize data.
     *
     * @var \Flugg\Responder\DataNormalizer
     */
    protected $normalizer;

    /**
     * A manager class, used to manage transformers.
     *
     * @var \Flugg\Responder\Contracts\Transformers\TransformerResolver
     */
    protected $transformerResolver;

    /**
     * Construct the factory class.
     *
     * @param \Flugg\Responder\Resources\DataNormalizer                   $normalizer
     * @param \Flugg\Responder\Contracts\Transformers\TransformerResolver $transformerResolver
     */
    public function __construct(DataNormalizer $normalizer, TransformerResolver $transformerResolver)
    {
        $this->normalizer = $normalizer;
        $this->transformerResolver = $transformerResolver;
    }

    /**
     * Make resource from the given data.
     *
     * @param  mixed                                                          $data
     * @param  \Flugg\Responder\Transformers\Transformer|string|callable|null $transformer
     * @param  string|null                                                    $resourceKey
     * @return \League\Fractal\Resource\ResourceInterface
     */
    public function make($data = null, $transformer = null, string $resourceKey = null): ResourceInterface
    {
        if (is_null($data = $this->normalizer->normalize($data))) {
            return $this->instatiateResource($data);
        }

        $transformer = $this->resolveTransformer($data, $transformer);

        return $this->instatiateResource($data, $transformer, $resourceKey);
    }

    /**
     * Resolve a transformer.
     *
     * @param  mixed                                                          $data
     * @param  \Flugg\Responder\Transformers\Transformer|string|callable|null $transformer
     * @return \Flugg\Responder\Transformers\Transformer|callable
     */
    protected function resolveTransformer($data, $transformer)
    {
        if (isset($transformer)) {
            return $this->transformerResolver->resolve($transformer);
        }

        return $this->transformerResolver->resolveFromData($data);
    }

    /**
     * Instatiate a new resource instance.
     *
     * @param  mixed                                                   $data
     * @param  \Flugg\Responder\Transformers\Transformer|callable|null $transformer
     * @param  string|null                                             $resourceKey
     * @return \League\Fractal\Resource\ResourceInterface
     */
    protected function instatiateResource($data, $transformer = null, string $resourceKey = null): ResourceInterface
    {
        if (is_null($data)) {
            return new NullResource();
        } elseif (is_array($data) || $data instanceof Traversable) {
            return new CollectionResource($data, $transformer, $resourceKey);
        }

        return new ItemResource($data, $transformer, $resourceKey);
    }
}
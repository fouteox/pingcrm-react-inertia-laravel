import { queryParams, type QueryParams } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:39
* @route '/horizon/api/batches'
*/
export const index = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ['get','head'],
    url: '/horizon/api/batches',
}

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:39
* @route '/horizon/api/batches'
*/
index.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:39
* @route '/horizon/api/batches'
*/
index.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:39
* @route '/horizon/api/batches'
*/
index.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:58
* @route '/horizon/api/batches/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ['get','head'],
    url: '/horizon/api/batches/{id}',
}

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:58
* @route '/horizon/api/batches/{id}'
*/
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    const parsedArgs = {
        id: args.id,
    }

    return show.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:58
* @route '/horizon/api/batches/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:58
* @route '/horizon/api/batches/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::retry
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:79
* @route '/horizon/api/batches/retry/{id}'
*/
export const retry = (args: { id: string | number } | [id: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'post',
} => ({
    url: retry.url(args, options),
    method: 'post',
})

retry.definition = {
    methods: ['post'],
    url: '/horizon/api/batches/retry/{id}',
}

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::retry
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:79
* @route '/horizon/api/batches/retry/{id}'
*/
retry.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    const parsedArgs = {
        id: args.id,
    }

    return retry.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::retry
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:79
* @route '/horizon/api/batches/retry/{id}'
*/
retry.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'post',
} => ({
    url: retry.url(args, options),
    method: 'post',
})

const jobsBatches = {
    index,
    show,
    retry,
}

export default jobsBatches
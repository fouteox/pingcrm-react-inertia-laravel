import { queryParams, type QueryParams } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ReverbExampleController::index
* @see app/Http/Controllers/ReverbExampleController.php:15
* @route '/reverb'
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
    url: '/reverb',
}

/**
* @see \App\Http\Controllers\ReverbExampleController::index
* @see app/Http/Controllers/ReverbExampleController.php:15
* @route '/reverb'
*/
index.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReverbExampleController::index
* @see app/Http/Controllers/ReverbExampleController.php:15
* @route '/reverb'
*/
index.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReverbExampleController::index
* @see app/Http/Controllers/ReverbExampleController.php:15
* @route '/reverb'
*/
index.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReverbExampleController::store
* @see app/Http/Controllers/ReverbExampleController.php:20
* @route '/reverb'
*/
export const store = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'post',
} => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ['post'],
    url: '/reverb',
}

/**
* @see \App\Http\Controllers\ReverbExampleController::store
* @see app/Http/Controllers/ReverbExampleController.php:20
* @route '/reverb'
*/
store.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReverbExampleController::store
* @see app/Http/Controllers/ReverbExampleController.php:20
* @route '/reverb'
*/
store.post = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'post',
} => ({
    url: store.url(options),
    method: 'post',
})

const ReverbExampleController = { index, store }

export default ReverbExampleController
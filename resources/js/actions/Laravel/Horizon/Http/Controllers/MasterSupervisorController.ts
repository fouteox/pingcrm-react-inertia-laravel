import { queryParams, type QueryParams } from './../../../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\MasterSupervisorController::index
* @see vendor/laravel/horizon/src/Http/Controllers/MasterSupervisorController.php:18
* @route '/horizon/api/masters'
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
    url: '/horizon/api/masters',
}

/**
* @see \Laravel\Horizon\Http\Controllers\MasterSupervisorController::index
* @see vendor/laravel/horizon/src/Http/Controllers/MasterSupervisorController.php:18
* @route '/horizon/api/masters'
*/
index.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\MasterSupervisorController::index
* @see vendor/laravel/horizon/src/Http/Controllers/MasterSupervisorController.php:18
* @route '/horizon/api/masters'
*/
index.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\MasterSupervisorController::index
* @see vendor/laravel/horizon/src/Http/Controllers/MasterSupervisorController.php:18
* @route '/horizon/api/masters'
*/
index.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: index.url(options),
    method: 'head',
})

const MasterSupervisorController = { index }

export default MasterSupervisorController
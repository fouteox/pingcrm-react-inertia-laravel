import { queryParams, type QueryParams } from './../../wayfinder'
/**
* @see \App\Http\Controllers\OrganizationsController::index
* @see app/Http/Controllers/OrganizationsController.php:19
* @route '/organizations'
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
    url: '/organizations',
}

/**
* @see \App\Http\Controllers\OrganizationsController::index
* @see app/Http/Controllers/OrganizationsController.php:19
* @route '/organizations'
*/
index.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrganizationsController::index
* @see app/Http/Controllers/OrganizationsController.php:19
* @route '/organizations'
*/
index.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrganizationsController::index
* @see app/Http/Controllers/OrganizationsController.php:19
* @route '/organizations'
*/
index.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrganizationsController::create
* @see app/Http/Controllers/OrganizationsController.php:33
* @route '/organizations/create'
*/
export const create = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ['get','head'],
    url: '/organizations/create',
}

/**
* @see \App\Http\Controllers\OrganizationsController::create
* @see app/Http/Controllers/OrganizationsController.php:33
* @route '/organizations/create'
*/
create.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrganizationsController::create
* @see app/Http/Controllers/OrganizationsController.php:33
* @route '/organizations/create'
*/
create.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrganizationsController::create
* @see app/Http/Controllers/OrganizationsController.php:33
* @route '/organizations/create'
*/
create.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrganizationsController::store
* @see app/Http/Controllers/OrganizationsController.php:38
* @route '/organizations'
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
    url: '/organizations',
}

/**
* @see \App\Http\Controllers\OrganizationsController::store
* @see app/Http/Controllers/OrganizationsController.php:38
* @route '/organizations'
*/
store.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrganizationsController::store
* @see app/Http/Controllers/OrganizationsController.php:38
* @route '/organizations'
*/
store.post = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'post',
} => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrganizationsController::edit
* @see app/Http/Controllers/OrganizationsController.php:45
* @route '/organizations/{organization}/edit'
*/
export const edit = (args: { organization: number | { id: number } } | [organization: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ['get','head'],
    url: '/organizations/{organization}/edit',
}

/**
* @see \App\Http\Controllers\OrganizationsController::edit
* @see app/Http/Controllers/OrganizationsController.php:45
* @route '/organizations/{organization}/edit'
*/
edit.url = (args: { organization: number | { id: number } } | [organization: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { organization: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { organization: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            organization: args[0],
        }
    }

    const parsedArgs = {
        organization: typeof args.organization === 'object'
        ? args.organization.id
        : args.organization,
    }

    return edit.definition.url
            .replace('{organization}', parsedArgs.organization.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrganizationsController::edit
* @see app/Http/Controllers/OrganizationsController.php:45
* @route '/organizations/{organization}/edit'
*/
edit.get = (args: { organization: number | { id: number } } | [organization: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrganizationsController::edit
* @see app/Http/Controllers/OrganizationsController.php:45
* @route '/organizations/{organization}/edit'
*/
edit.head = (args: { organization: number | { id: number } } | [organization: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrganizationsController::update
* @see app/Http/Controllers/OrganizationsController.php:52
* @route '/organizations/{organization}'
*/
export const update = (args: { organization: number | { id: number } } | [organization: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'put',
} => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ['put','patch'],
    url: '/organizations/{organization}',
}

/**
* @see \App\Http\Controllers\OrganizationsController::update
* @see app/Http/Controllers/OrganizationsController.php:52
* @route '/organizations/{organization}'
*/
update.url = (args: { organization: number | { id: number } } | [organization: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { organization: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { organization: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            organization: args[0],
        }
    }

    const parsedArgs = {
        organization: typeof args.organization === 'object'
        ? args.organization.id
        : args.organization,
    }

    return update.definition.url
            .replace('{organization}', parsedArgs.organization.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrganizationsController::update
* @see app/Http/Controllers/OrganizationsController.php:52
* @route '/organizations/{organization}'
*/
update.put = (args: { organization: number | { id: number } } | [organization: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'put',
} => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\OrganizationsController::update
* @see app/Http/Controllers/OrganizationsController.php:52
* @route '/organizations/{organization}'
*/
update.patch = (args: { organization: number | { id: number } } | [organization: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'patch',
} => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\OrganizationsController::destroy
* @see app/Http/Controllers/OrganizationsController.php:59
* @route '/organizations/{organization}'
*/
export const destroy = (args: { organization: number | { id: number } } | [organization: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'delete',
} => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ['delete'],
    url: '/organizations/{organization}',
}

/**
* @see \App\Http\Controllers\OrganizationsController::destroy
* @see app/Http/Controllers/OrganizationsController.php:59
* @route '/organizations/{organization}'
*/
destroy.url = (args: { organization: number | { id: number } } | [organization: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { organization: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { organization: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            organization: args[0],
        }
    }

    const parsedArgs = {
        organization: typeof args.organization === 'object'
        ? args.organization.id
        : args.organization,
    }

    return destroy.definition.url
            .replace('{organization}', parsedArgs.organization.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrganizationsController::destroy
* @see app/Http/Controllers/OrganizationsController.php:59
* @route '/organizations/{organization}'
*/
destroy.delete = (args: { organization: number | { id: number } } | [organization: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'delete',
} => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\OrganizationsController::restore
* @see app/Http/Controllers/OrganizationsController.php:66
* @route '/organizations/{organization}/restore'
*/
export const restore = (args: { organization: number | { id: number } } | [organization: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'put',
} => ({
    url: restore.url(args, options),
    method: 'put',
})

restore.definition = {
    methods: ['put'],
    url: '/organizations/{organization}/restore',
}

/**
* @see \App\Http\Controllers\OrganizationsController::restore
* @see app/Http/Controllers/OrganizationsController.php:66
* @route '/organizations/{organization}/restore'
*/
restore.url = (args: { organization: number | { id: number } } | [organization: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { organization: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { organization: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            organization: args[0],
        }
    }

    const parsedArgs = {
        organization: typeof args.organization === 'object'
        ? args.organization.id
        : args.organization,
    }

    return restore.definition.url
            .replace('{organization}', parsedArgs.organization.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrganizationsController::restore
* @see app/Http/Controllers/OrganizationsController.php:66
* @route '/organizations/{organization}/restore'
*/
restore.put = (args: { organization: number | { id: number } } | [organization: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'put',
} => ({
    url: restore.url(args, options),
    method: 'put',
})

const organizations = {
    index,
    create,
    store,
    edit,
    update,
    destroy,
    restore,
}

export default organizations
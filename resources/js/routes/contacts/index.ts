import { queryParams, type QueryParams } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ContactsController::index
* @see app/Http/Controllers/ContactsController.php:21
* @route '/contacts'
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
    url: '/contacts',
}

/**
* @see \App\Http\Controllers\ContactsController::index
* @see app/Http/Controllers/ContactsController.php:21
* @route '/contacts'
*/
index.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ContactsController::index
* @see app/Http/Controllers/ContactsController.php:21
* @route '/contacts'
*/
index.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ContactsController::index
* @see app/Http/Controllers/ContactsController.php:21
* @route '/contacts'
*/
index.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ContactsController::create
* @see app/Http/Controllers/ContactsController.php:36
* @route '/contacts/create'
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
    url: '/contacts/create',
}

/**
* @see \App\Http\Controllers\ContactsController::create
* @see app/Http/Controllers/ContactsController.php:36
* @route '/contacts/create'
*/
create.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ContactsController::create
* @see app/Http/Controllers/ContactsController.php:36
* @route '/contacts/create'
*/
create.get = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ContactsController::create
* @see app/Http/Controllers/ContactsController.php:36
* @route '/contacts/create'
*/
create.head = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ContactsController::store
* @see app/Http/Controllers/ContactsController.php:48
* @route '/contacts'
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
    url: '/contacts',
}

/**
* @see \App\Http\Controllers\ContactsController::store
* @see app/Http/Controllers/ContactsController.php:48
* @route '/contacts'
*/
store.url = (options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ContactsController::store
* @see app/Http/Controllers/ContactsController.php:48
* @route '/contacts'
*/
store.post = (options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'post',
} => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ContactsController::edit
* @see app/Http/Controllers/ContactsController.php:55
* @route '/contacts/{contact}/edit'
*/
export const edit = (args: { contact: number | { id: number } } | [contact: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ['get','head'],
    url: '/contacts/{contact}/edit',
}

/**
* @see \App\Http\Controllers\ContactsController::edit
* @see app/Http/Controllers/ContactsController.php:55
* @route '/contacts/{contact}/edit'
*/
edit.url = (args: { contact: number | { id: number } } | [contact: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { contact: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { contact: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            contact: args[0],
        }
    }

    const parsedArgs = {
        contact: typeof args.contact === 'object'
        ? args.contact.id
        : args.contact,
    }

    return edit.definition.url
            .replace('{contact}', parsedArgs.contact.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ContactsController::edit
* @see app/Http/Controllers/ContactsController.php:55
* @route '/contacts/{contact}/edit'
*/
edit.get = (args: { contact: number | { id: number } } | [contact: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'get',
} => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ContactsController::edit
* @see app/Http/Controllers/ContactsController.php:55
* @route '/contacts/{contact}/edit'
*/
edit.head = (args: { contact: number | { id: number } } | [contact: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'head',
} => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ContactsController::update
* @see app/Http/Controllers/ContactsController.php:67
* @route '/contacts/{contact}'
*/
export const update = (args: { contact: number | { id: number } } | [contact: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'put',
} => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ['put','patch'],
    url: '/contacts/{contact}',
}

/**
* @see \App\Http\Controllers\ContactsController::update
* @see app/Http/Controllers/ContactsController.php:67
* @route '/contacts/{contact}'
*/
update.url = (args: { contact: number | { id: number } } | [contact: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { contact: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { contact: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            contact: args[0],
        }
    }

    const parsedArgs = {
        contact: typeof args.contact === 'object'
        ? args.contact.id
        : args.contact,
    }

    return update.definition.url
            .replace('{contact}', parsedArgs.contact.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ContactsController::update
* @see app/Http/Controllers/ContactsController.php:67
* @route '/contacts/{contact}'
*/
update.put = (args: { contact: number | { id: number } } | [contact: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'put',
} => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ContactsController::update
* @see app/Http/Controllers/ContactsController.php:67
* @route '/contacts/{contact}'
*/
update.patch = (args: { contact: number | { id: number } } | [contact: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'patch',
} => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ContactsController::destroy
* @see app/Http/Controllers/ContactsController.php:74
* @route '/contacts/{contact}'
*/
export const destroy = (args: { contact: number | { id: number } } | [contact: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'delete',
} => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ['delete'],
    url: '/contacts/{contact}',
}

/**
* @see \App\Http\Controllers\ContactsController::destroy
* @see app/Http/Controllers/ContactsController.php:74
* @route '/contacts/{contact}'
*/
destroy.url = (args: { contact: number | { id: number } } | [contact: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { contact: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { contact: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            contact: args[0],
        }
    }

    const parsedArgs = {
        contact: typeof args.contact === 'object'
        ? args.contact.id
        : args.contact,
    }

    return destroy.definition.url
            .replace('{contact}', parsedArgs.contact.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ContactsController::destroy
* @see app/Http/Controllers/ContactsController.php:74
* @route '/contacts/{contact}'
*/
destroy.delete = (args: { contact: number | { id: number } } | [contact: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'delete',
} => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ContactsController::restore
* @see app/Http/Controllers/ContactsController.php:81
* @route '/contacts/{contact}/restore'
*/
export const restore = (args: { contact: number | { id: number } } | [contact: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'put',
} => ({
    url: restore.url(args, options),
    method: 'put',
})

restore.definition = {
    methods: ['put'],
    url: '/contacts/{contact}/restore',
}

/**
* @see \App\Http\Controllers\ContactsController::restore
* @see app/Http/Controllers/ContactsController.php:81
* @route '/contacts/{contact}/restore'
*/
restore.url = (args: { contact: number | { id: number } } | [contact: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { contact: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { contact: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            contact: args[0],
        }
    }

    const parsedArgs = {
        contact: typeof args.contact === 'object'
        ? args.contact.id
        : args.contact,
    }

    return restore.definition.url
            .replace('{contact}', parsedArgs.contact.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ContactsController::restore
* @see app/Http/Controllers/ContactsController.php:81
* @route '/contacts/{contact}/restore'
*/
restore.put = (args: { contact: number | { id: number } } | [contact: number | { id: number } ] | number | { id: number }, options?: { query?: QueryParams, mergeQuery?: QueryParams }): {
    url: string,
    method: 'put',
} => ({
    url: restore.url(args, options),
    method: 'put',
})

const contacts = {
    index,
    create,
    store,
    edit,
    update,
    destroy,
    restore,
}

export default contacts
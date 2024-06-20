import { useState } from "react";
import { router, usePage } from '@inertiajs/react'
import SelectInput from "@/Shared/SelectInput";

export default () => {
    const { filters } = usePage().props;
    const [opened, setOpened] = useState(false);

    const [values, setValues] = useState({
        role: filters.role || "", // role is used only on users page
        search: filters.search || "",
        trashed: filters.trashed || "",
    });

    function reset() {
        setValues({
            role: "",
            search: "",
            trashed: "",
        });

        router.get(route(route().current()), {
            replace: true,
            preserveState: true,
        });
    }

    function pickBy(object) {
        const obj = {};
        for (const key in object) {
            if (object[key]) {
                obj[key] = object[key];
            }
        }
        return obj;
    }

    function handleChange(e) {
        const key = e.target.name;
        const value = e.target.value;

        const newValues = {
            ...values,
            [key]: value,
        };

        setValues(newValues);

        const query = pickBy(newValues);

        router.get(route(route().current()), query, {
            replace: true,
            preserveState: true,
        });

        if (opened) setOpened(false);
    }

    return (
        <div className="flex items-center w-full max-w-md mr-4">
            <div className="relative flex w-full bg-white rounded shadow">
                <div
                    style={{ top: "100%" }}
                    className={`absolute ${opened ? "" : "hidden"}`}
                >
                    <div
                        onClick={() => setOpened(false)}
                        className="fixed inset-0 z-20 bg-black opacity-25"
                    ></div>
                    <div className="relative z-30 w-64 px-4 py-6 mt-2 bg-white rounded shadow-lg">
                        {filters.hasOwnProperty("role") && (
                            <SelectInput
                                className="mb-4"
                                label="Role"
                                name="role"
                                value={values.role}
                                onChange={handleChange}
                            >
                                <option value=""></option>
                                <option value="user">User</option>
                                <option value="owner">Owner</option>
                            </SelectInput>
                        )}
                        <SelectInput
                            label="Trashed"
                            name="trashed"
                            value={values.trashed}
                            onChange={handleChange}
                        >
                            <option value=""></option>
                            <option value="with">With Trashed</option>
                            <option value="only">Only Trashed</option>
                        </SelectInput>
                    </div>
                </div>
                <button
                    onClick={() => setOpened(true)}
                    className="px-4 border-r rounded-l md:px-6 hover:bg-gray-100 focus:outline-none focus:border-white focus:ring focus:ring-indigo-400/50 focus:z-10"
                >
                    <div className="flex items-baseline">
                        <span className="hidden text-gray-700 md:inline">
                            Filter
                        </span>
                        <svg
                            className="w-2 h-2 text-gray-700 fill-current md:ml-2"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 961.243 599.998"
                        >
                            <path d="M239.998 239.999L0 0h961.243L721.246 240c-131.999 132-240.28 240-240.624 239.999-.345-.001-108.625-108.001-240.624-240z" />
                        </svg>
                    </div>
                </button>
                <input
                    className="relative w-full px-6 py-2 border-none rounded-r focus:shadow-outline focus:ring-2"
                    autoComplete="off"
                    type="text"
                    name="search"
                    value={values.search}
                    onChange={handleChange}
                    placeholder="Search…"
                />
            </div>
            <button
                onClick={reset}
                className="ml-3 text-sm text-gray-600 hover:text-gray-700 focus:text-indigo-700 focus:outline-none"
                type="button"
            >
                Reset
            </button>
        </div>
    );
};

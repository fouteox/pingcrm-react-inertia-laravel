export default ({
    name,
    className,
    children,
    ...props
}) => {
    return (
        <div className={className}>
            <select
                id={name}
                name={name}
                {...props}
                className={`p-2 leading-normal block w-full border border-gray-200 text-gray-700 bg-white font-sans rounded text-left appearance-none relative focus:ring-indigo-400/50 focus:ring`}
            >
                {children}
            </select>
        </div>
    );
};

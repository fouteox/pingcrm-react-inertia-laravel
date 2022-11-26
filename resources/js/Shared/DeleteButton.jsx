export default ({ onClick, children }) => (
    <button
        className="text-red-600 focus:outline-none hover:underline"
        tabIndex="-1"
        type="button"
        onClick={onClick}
    >
        {children}
    </button>
);

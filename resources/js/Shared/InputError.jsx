export default function InputError({ message, className = "" }) {
    return message ? (
        <div className={"text-sm text-red-700 mt-2 " + className}>
            {message}
        </div>
    ) : null;
}

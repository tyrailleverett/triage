export default function Reports(): React.JSX.Element {
    return (
        <div className="p-6">
            <div className="mb-6">
                <h1 className="text-xl font-semibold text-white">Reports</h1>
                <p className="mt-1 text-sm text-gray-400">
                    Reporting surfaces will expand in a later phase. This screen keeps the dashboard navigation complete.
                </p>
            </div>

            <div className="grid gap-4 md:grid-cols-3">
                <div className="rounded-lg border border-white/10 bg-white/5 p-5">
                    <p className="text-xs font-medium uppercase tracking-wide text-gray-500">Queue Health</p>
                    <p className="mt-3 text-lg font-semibold text-white">Available in Phase 7+</p>
                </div>

                <div className="rounded-lg border border-white/10 bg-white/5 p-5">
                    <p className="text-xs font-medium uppercase tracking-wide text-gray-500">Response Time</p>
                    <p className="mt-3 text-lg font-semibold text-white">Available in Phase 7+</p>
                </div>

                <div className="rounded-lg border border-white/10 bg-white/5 p-5">
                    <p className="text-xs font-medium uppercase tracking-wide text-gray-500">Resolution Trends</p>
                    <p className="mt-3 text-lg font-semibold text-white">Available in Phase 7+</p>
                </div>
            </div>
        </div>
    );
}
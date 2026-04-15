export default function AdminFooter() {
    return (
        <footer className="shrink-0 border-t border-slate-200 bg-white px-4 py-3 text-xs text-slate-500 lg:px-8">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <span>PartnerSales Admin Portal</span>
                <span>{new Date().getFullYear()} | Analytics • Security • Reconciliation</span>
            </div>
        </footer>
    );
}

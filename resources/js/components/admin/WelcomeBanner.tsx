interface WelcomeBannerProps {
    name?: string;
    text?: string;
}

export default function WelcomeBanner({ name, text }: WelcomeBannerProps) {
    return (
        <div className="relative overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-600 to-teal-600 p-5 text-white shadow-sm">
            <div className="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10" />
            <div className="absolute -bottom-8 right-20 h-24 w-24 rounded-full bg-white/10" />
            <p className="text-sm text-emerald-100">Welcome back{name ? `, ${name}` : ''}</p>
            <h2 className="mt-1 text-2xl font-semibold tracking-tight">Platform command center</h2>
            <p className="mt-1 text-sm text-emerald-100">{text ?? 'Track operations, revenue, and customer coverage in real time.'}</p>
        </div>
    );
}

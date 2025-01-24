import { Link } from '@/Components/link';
import ThemeToggleButton from '@/Layout/ThemeToggleButton';

export default function Home() {
    return (
        <div className="bg-white lg:bg-zinc-100 dark:bg-zinc-900 dark:lg:bg-zinc-950">
            <header className="absolute inset-x-0 top-0 z-50">
                <div className="mx-auto max-w-7xl">
                    <div className="px-6 pt-6 lg:max-w-2xl lg:pr-0 lg:pl-8">
                        <nav aria-label="Global" className="flex items-center justify-between">
                            <a href="#" className="-m-1.5 p-1.5">
                                <span className="sr-only">Your Company</span>
                                <img
                                    alt="Your Company"
                                    src="https://tailwindui.com/plus/img/logos/mark.svg?color=teal&shade=600"
                                    className="h-8 w-auto"
                                />
                            </a>
                            <ThemeToggleButton />
                        </nav>
                    </div>
                </div>
                <div className="absolute inset-x-0 top-0 z-50 flex justify-end p-4"></div>
            </header>

            <div className="relative">
                <div className="mx-auto max-w-7xl">
                    <div className="relative z-10 pt-14 lg:w-full lg:max-w-2xl">
                        <svg
                            viewBox="0 0 100 100"
                            preserveAspectRatio="none"
                            aria-hidden="true"
                            className="absolute inset-y-0 right-8 hidden h-full w-80 translate-x-1/2 transform fill-white lg:fill-zinc-100 dark:fill-zinc-900 dark:lg:fill-zinc-950 lg:block"
                        >
                            <polygon points="0,0 90,0 50,100 0,100" />
                        </svg>

                        <div className="relative px-6 py-32 sm:py-40 lg:px-8 lg:py-56 lg:pr-0">
                            <div className="mx-auto max-w-2xl lg:mx-0 lg:max-w-xl">
                                <h1 className="text-5xl font-semibold tracking-tight text-pretty text-gray-900 dark:text-gray-100 sm:text-7xl">
                                    Supercharge Discord with AI Power
                                </h1>
                                <p className="mt-8 text-lg font-medium text-pretty text-gray-500 dark:text-gray-400 sm:text-xl/8">
                                    Unlock the full potential of your Discord server with our AI-driven bot. From
                                    automating tasks to delivering personalized experiences and powerful insights, our
                                    bot seamlessly integrates cutting-edge AI features to make managing and engaging
                                    your community effortless. Elevate your server today!
                                </p>
                                <div className="mt-10 flex items-center gap-x-6">
                                    <Link
                                        href={route('login')}
                                        className="rounded-md bg-teal-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-teal-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600"
                                    >
                                        Get started
                                    </Link>
                                    <Link href="#" className="text-sm/6 font-semibold text-gray-900 dark:text-gray-100">
                                        Learn more <span aria-hidden="true">→</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="bg-gray-50 dark:bg-zinc-900 lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
                    <img
                        alt=""
                        src="https://cdn.metacomet.tech/bot/bot-home.png"
                        className="aspect-3/2 object-cover lg:aspect-auto lg:size-full"
                    />
                </div>
            </div>
        </div>
    );
}

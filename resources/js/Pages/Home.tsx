import { Button } from '@/Components/button';
import { Link } from '@/Components/link';
import { useTheme } from '@/Layout/ThemeContext';
import ThemeToggleButton from '@/Layout/ThemeToggleButton';
import { PageProps } from '@/types';
import { ArrowRightIcon } from '@heroicons/react/16/solid';
import { Head, usePage } from '@inertiajs/react';

export default function Home() {
    const { auth } = usePage<PageProps>().props;
    const { theme } = useTheme();

    return (
        <>
            <Head title="Home">
                <meta name="description" content="Supercharge Discord with AI Power" />
                <link
                    rel="preload"
                    fetchPriority="high"
                    as="image"
                    href="https://cdn.neon-bot.com/home-bg.png"
                    type="image/png"
                />
            </Head>
            <div className="bg-white lg:bg-zinc-100 dark:bg-zinc-900 dark:lg:bg-zinc-950">
                <header className="absolute inset-x-0 top-0 z-50">
                    <div className="mx-auto max-w-7xl">
                        <div className="px-6 pt-6 lg:max-w-2xl lg:pr-0 lg:pl-8">
                            <nav aria-label="Global" className="flex items-center justify-between">
                                <Link href={route('home')} className="-m-1.5 p-1.5">
                                    <span className="sr-only">Neon</span>
                                    <img
                                        src="https://cdn.neon-bot.com/logo/pink-600/PNG/neon@4x.png"
                                        className="w-auto h-24"
                                    />
                                </Link>
                                <div className="flex flex-row items-center gap-x-4">
                                    <Button plain href={route('server.index')}>
                                        {auth.user ? 'Dashboard' : 'Login'}
                                    </Button>

                                    <ThemeToggleButton name="theme-toggle-button" />
                                </div>
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
                                    <div className="mb-10 flex">
                                        <div className="relative rounded-full px-3 py-1 text-sm/6 text-zinc-500 dark:text-zinc-400 ring-1 ring-zinc-900/10 dark:ring-zinc-700 hover:ring-zinc-900/20 dark:hover:ring-zinc-600">
                                            Coming Soon...{' '}
                                            {/* TODO: add a route to register the user and provide them a thanks for signing up screen. */}
                                            <Link
                                                href="#"
                                                className="font-semibold whitespace-nowrap text-pink-600 dark:text-cyan-300"
                                            >
                                                <span aria-hidden="true" className="absolute inset-0" />
                                                Join Our Mailing List <span aria-hidden="true">&rarr;</span>
                                            </Link>
                                        </div>
                                    </div>
                                    <h1 className="text-5xl font-semibold tracking-tight text-pretty text-zinc-900 dark:text-pink-600 sm:text-7xl">
                                        Supercharge Discord with AI Power
                                    </h1>
                                    <p className="mt-8 text-lg font-medium text-pretty text-zinc-500 dark:text-zinc-400 sm:text-xl/8">
                                        Unlock the full potential of your Discord server with our AI-driven bot. From
                                        automating tasks to delivering personalized experiences and powerful insights,
                                        our bot seamlessly integrates cutting-edge AI features to make managing and
                                        engaging your community effortless. Elevate your server today!
                                    </p>
                                    <div className="mt-10 flex items-center gap-x-6">
                                        <Button
                                            color={theme === 'light' ? 'cyan' : 'pink'}
                                            href={route('server.index')}
                                        >
                                            Get started
                                        </Button>
                                        <Button plain href={route('server.index')}>
                                            Learn more <ArrowRightIcon />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="bg-zinc-50 dark:bg-zinc-900 lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
                        <img
                            alt="gamer at desk"
                            src="https://cdn.neon-bot.com/home-bg.png"
                            className="aspect-3/2 object-cover lg:aspect-auto lg:size-full"
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

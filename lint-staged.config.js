export default {
    'resources/{css,js}/**/*.{css,js}': ['prettier --write', 'git add'],
    'resources/**/*': () => ['npm run build', 'git add resources/dist'],
    '**/*.php': ['./vendor/bin/pint'],
};

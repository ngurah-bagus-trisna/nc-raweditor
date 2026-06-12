.PHONY: build-js build-js-production watch-js lint composer-install

build-js:
	npm run dev

build-js-production:
	npm run build

watch-js:
	npm run watch

lint:
	npm run lint

composer-install:
	composer install --no-dev -o

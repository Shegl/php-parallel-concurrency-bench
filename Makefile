.PHONY: bench-single bench-single-jit \
	test-jit bench-pipes bench-pipes-jit \
	bench-fork-sockets bench-fork-sockets-jit \
	bench-fork-sockets-fibers bench-fork-sockets-fibers-jit \
	docker-build docker-build-zts docker-run docker-run-zts

ROOT_DIR := $(dir $(realpath $(lastword $(MAKEFILE_LIST))))
CMD ?= make test-jit

docker-build:
	docker build -t php-concurrency-benches-jit -f docker/jit/Dockerfile .

docker-build-zts:
	docker build -t php-concurrency-benches-zts -f docker/zts/Dockerfile .

docker-run:
	docker run php-concurrency-benches-jit $(CMD)

docker-run-zts:
	docker run php-concurrency-benches-zts $(CMD)

test-jit:
	php -d opcache.enable=1 \
		-d opcache.enable_cli=1 \
		-d opcache.jit=0000 \
		$(ROOT_DIR)src/test-jit.php

test-jit-on:
	php -d opcache.enable=1 \
		-d opcache.enable_cli=1 \
		-d opcache.jit_buffer_size=100M \
		-d opcache.jit=1255 \
		$(ROOT_DIR)src/test-jit.php

bench-single:
	php -d opcache.enable=1 \
		-d opcache.enable_cli=1 \
		-d opcache.jit=0000 \
		$(ROOT_DIR)src/single/entry.php 1000000

bench-single-jit:
	php -d opcache.enable=1 \
		-d opcache.enable_cli=1 \
		-d opcache.jit_buffer_size=100M \
		-d opcache.jit=1255 \
		$(ROOT_DIR)src/single/entry.php 1000000

bench-pipes:
	php -d opcache.enable=1 \
		-d opcache.enable_cli=1 \
		-d opcache.jit=0000 \
		$(ROOT_DIR)src/pipes/entry.php 1000000

bench-pipes-jit:
	php -d opcache.enable=1 \
		-d opcache.enable_cli=1 \
		-d opcache.jit_buffer_size=100M \
		-d opcache.jit=1255 \
		$(ROOT_DIR)src/pipes/entry.php 1000000

bench-fork-sockets:
	php -d opcache.enable=1 \
		-d opcache.enable_cli=1 \
		-d opcache.jit=0000 \
		$(ROOT_DIR)src/fork-sockets/entry.php 1000000

bench-fork-sockets-jit:
	php -d opcache.enable=1 \
		-d opcache.enable_cli=1 \
		-d opcache.jit_buffer_size=100M \
		-d opcache.jit=1255 \
		$(ROOT_DIR)src/fork-sockets/entry.php 1000000

bench-fork-sockets-fibers:
	php -d opcache.enable=1 \
		-d opcache.enable_cli=1 \
		-d opcache.jit=0000 \
		$(ROOT_DIR)src/fork-sockets-fibers/entry.php 1000000

bench-fork-sockets-fibers-jit:
	php -d opcache.enable=1 \
		-d opcache.enable_cli=1 \
		-d opcache.jit_buffer_size=100M \
		-d opcache.jit=1255 \
		$(ROOT_DIR)src/fork-sockets-fibers/entry.php 1000000
## Requirement

- Be sure you have php 8.2 on your machine.
- Be sure you have docker on your machine.
- Be sure you have symfony cli on your machine.
- Create .makefile/composer.mk files and put you're composer token in it:
```
GITHUB_TOKEN ?= ghp_xxxxxxxxxxxxx
```
https://getcomposer.org/doc/articles/authentication-for-private-packages.md#github-oauth

## Installation

`make install`

The installation will download and configure default sylius standard version with this bundle directly installed as a symlinked dependency.

The application is then available at http://localhost:8099. The host ports avoid the ones DDEV uses, so the project runs even when DDEV projects are up:

| Service | URL or port |
|---------|-------------|
| Shop and admin | http://localhost:8099 (admin on `/admin`) |
| MySQL | `127.0.0.1:3337` |
| Mailhog | http://localhost:8045 |

If one of these ports is already taken on your machine, create a `.env` file at the project root (it is git-ignored) and run `make up` again:

```
HTTP_PORT=8100
MYSQL_PORT=3338
MAILHOG_PORT=8046
```

## Usage

### List all available commands

`make help`

### Stop

`make down`

### Reset project

`make reset`


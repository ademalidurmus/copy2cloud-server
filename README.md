# copy2cloud-server

copy2cloud-server is a internet clipboard service.

[![License](https://img.shields.io/github/license/ademalidurmus/copy2cloud-server)](https://github.com/ademalidurmus/copy2cloud-server/blob/master/LICENSE)
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Fademalidurmus%2Fcopy2cloud-server.svg?type=shield)](https://app.fossa.com/projects/git%2Bgithub.com%2Fademalidurmus%2Fcopy2cloud-server?ref=badge_shield)
[![Build Status](https://travis-ci.org/ademalidurmus/copy2cloud-server.svg?branch=master)](https://travis-ci.org/ademalidurmus/copy2cloud-server)
[![codecov](https://codecov.io/gh/ademalidurmus/copy2cloud-server/branch/master/graph/badge.svg?token=N737QM5KHP)](https://codecov.io/gh/ademalidurmus/copy2cloud-server)

## API Documentation

API documentation available at [Postman Public Directory](https://documenter.getpostman.com/view/5001481/UVeMJj4S)

## Installation

Download and run project with following commands.

```sh
# download project
git clone https://github.com/ademalidurmus/copy2cloud-server.git

# go to project folder
cd copy2cloud-server

# copy environment file from .env.dist (you can create own .env file or modify created file)
make env

# build project
make build

# check application status on your browser, httpie or REST API client
http localhost:1453/v1/ping
```

## License

MIT © [Adem Ali Durmuş](https://github.com/ademalidurmus)
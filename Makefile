# Nextcloud CRM App - Makefile
# Pour créer des releases propres et gérer les builds

app_name=crm
build_dir=$(CURDIR)/build
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
sign_dir=$(build_dir)/sign
package_name=$(app_name)
cert_dir=$(HOME)/.nextcloud/certificates
version=$(shell sed -n 's/.*<version>\(.*\)<\/version>.*/\1/p' appinfo/info.xml)

all: clean build

# Clean build artifacts
clean:
	rm -rf $(build_dir)
	rm -rf node_modules
	rm -rf vendor
	rm -rf vendor-bin/*/vendor

# Install dependencies
install:
	npm install
	composer install --no-dev
	composer bin all install --no-dev

# Build frontend assets
build-js:
	npm run build

# Run all tests
test:
	npm run test:all

# Build the app for release
build: install build-js
	mkdir -p $(source_dir)
	rsync -a \
		--exclude=/.git \
		--exclude=/.github \
		--exclude=/build \
		--exclude=/node_modules \
		--exclude=/vendor-bin \
		--exclude=/.vscode \
		--exclude=/test \
		--exclude=/tests \
		--exclude=/docs \
		--exclude=/*.log \
		--exclude=/*.md \
		--exclude=/.*ignore \
		--exclude=/Makefile \
		--exclude=/webpack.config.js \
		--exclude=/tsconfig.json \
		--exclude=/jest.config.js \
		--exclude=/playwright.config.ts \
		--exclude=/rector.php \
		--exclude=/stylelint.config.cjs \
		--exclude=/composer.json \
		--exclude=/composer.lock \
		--exclude=/package.json \
		--exclude=/package-lock.json \
		--exclude=/*.ps1 \
		--exclude=/*.sh \
		--exclude=/src \
		$(CURDIR)/ $(source_dir)/$(app_name)

# Create appstore package
appstore: build
	mkdir -p $(sign_dir)
	mkdir -p $(appstore_dir)
	@echo "Creating archive..."
	cd $(source_dir) && tar -czf $(appstore_dir)/$(package_name)-$(version).tar.gz $(app_name)
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing package..."; \
		openssl dgst -sha512 -sign $(cert_dir)/$(app_name).key $(appstore_dir)/$(package_name)-$(version).tar.gz | openssl base64 > $(appstore_dir)/$(package_name)-$(version).tar.gz.sig; \
	else \
		echo "Warning: No certificate found at $(cert_dir)/$(app_name).key - package not signed"; \
	fi
	@echo "Package created: $(appstore_dir)/$(package_name)-$(version).tar.gz"
	@echo "Version: $(version)"

# Create appstore package without signature (for testing)
appstore-unsigned: build
	mkdir -p $(appstore_dir)
	@echo "Creating archive (unsigned)..."
	cd $(source_dir) && tar -czf $(appstore_dir)/$(package_name)-$(version).tar.gz $(app_name)
	@echo "Package created: $(appstore_dir)/$(package_name)-$(version).tar.gz"
	@echo "Version: $(version)"

# Update version in info.xml
set-version:
	@if [ -z "$(VERSION)" ]; then \
		echo "Usage: make set-version VERSION=x.y.z"; \
		exit 1; \
	fi
	@sed -i.bak 's/<version>.*<\/version>/<version>$(VERSION)<\/version>/' appinfo/info.xml
	@rm -f appinfo/info.xml.bak
	@echo "Version updated to $(VERSION) in appinfo/info.xml"
	@echo "Don't forget to update CHANGELOG.md and package.json!"

# Show current version
show-version:
	@echo "Current version: $(version)"

.PHONY: all clean install build-js test build appstore appstore-unsigned set-version show-version

include Makefile.in

.PHONY: all

all:

install:
	@echo Install the SHIT project...
	install -m755 fakeAP $(PREFIX)/sbin/fakeAP
	install -m755 StevenFilter $(PREFIX)/sbin/sf

uninstall:
	rm -f $(PREFIX)/sbin/fakeAP
	rm -f $(PREFIX)/sbin/sf

serve:
	@echo Serving SHIT web UI on http://0.0.0.0:5538 ...
	php -S 0.0.0.0:5538 -t app/public router.php

run:
	@echo Run SHIT ...
	./fakeAP start

stop:
	@echo Stop SHIT ...
	./fakeAP stop

distclean:
	rm -rf Makefile.in fakeAP.conf

daily-test:
	@echo "*/10 * * * * cd ${PWD} && git pull --rebase && ./configure && make" | crontab -

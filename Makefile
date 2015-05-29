include Makefile.in

.PHONY: all

all:

install:
	@echo Install the SHIT project...
	install -m755 fakeAP $(PREFIX)/sbin/fakeAP

uninstall:
	rm -f $(PREFIX)/sbin/fakeAP

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

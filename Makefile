include Makefile.in

.PHONY: all

all:

install:
	@echo Install the SHIT project...
	${UPDATE}
	${INSTALL} ${PKGS}

run:
	@echo Run SHIT ...
	./fakeAP start

stop:
	@echo Stop SHIT ...
	./fakeAP stop

daily-test:
	@echo "*/10 * * * * cd ${PWD} && git pull --rebase && ./configure && make" | crontab -

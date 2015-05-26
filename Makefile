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

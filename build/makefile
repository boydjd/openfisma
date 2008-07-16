#
# CopyRight : MIT Licence
# $Id$
#
# To build a release package and documentation
#

PROJECT = OpenFISMA
CODE = S
VERSION =2.0

TAR = tar
DOXYGEN = doxygen

WKDIR = output
NAME = $(PROJECT)-$(CODE)-$(VERSION)
ROOT = $(WKDIR)

src = $(NAME)/public     \
      $(NAME)/log        \
      $(NAME)/vendor     \
      $(NAME)/apps       \
      $(NAME)/conf       \
      $(NAME)/paths.php  \
      $(NAME)/bin        \
      $(NAME)/include    \
      $(NAME)/index.html \
      $(NAME)/INSTALL

dev = $(NAME)/test       \
      $(NAME)/DEVELOP    \
      $(src)

all: release dev-rel

exclude_pattern = --exclude=.svn --exclude=templates_c/* --exclude=tmp/*

release: prepare $(NAME).tgz
dev-rel: prepare $(NAME)_dev.tgz
doc:

prepare: $(WKDIR)
	-ln -s $(CURDIR)/../ $(ROOT)/$(NAME)

$(WKDIR):
	if [ ! -d "$@" ]; then mkdir $@ ;fi

$(NAME).tgz: $(addprefix $(ROOT)/,$(src))
	$(TAR) -czf $@ $^ $(exclude_pattern)

$(NAME)_dev.tgz: $(addprefix $(ROOT)/,$(dev))
	$(TAR) czf $@ $^ $(exclude_pattern)

doc:
	$(doxgen) -w $(ROOT)/$(NAME)/docs/Doxyfile
	$(doxgen) -w $(ROOT)/$(NAME)/docs/publishDoc

clean:
	-rm $(ROOT)/$(NAME)
	-rm -r $(WKDIR)
	-rm $(NAME)*tgz


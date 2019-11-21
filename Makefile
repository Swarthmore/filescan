ORIG_DIR := amd/orig
SRC_DIR := amd/src
ORIGINALS := $(wildcard $(ORIG_DIR)/*.js)
OBJ := $(patsubst $(ORIG_DIR)/%,$(SRC_DIR)/%,$(ORIGINALS))

all: grunt purge

# Convert all the downloaded DataTables files to local format.
convert: $(OBJ)

grunt:
	cd amd && grunt

purge:
	php $(PWD)/../../admin/cli/purge_caches.php

# Convert a DataTables javascript file to work in the Moodle AMD environment.
$(SRC_DIR)/%.js: $(ORIG_DIR)/%.js
	perl -C convert.pl $< >$@

$(OBJ): convert.pl

clean:
	-rm $(OBJ)

DISTFILE := block_filescan.zip

dist:
	cd .. && { rm -f $(DISTFILE); zip -r $(DISTFILE) datatables -x 'datatables/.git*' '*~'; }

.PHONY: all clean convert purge grunt

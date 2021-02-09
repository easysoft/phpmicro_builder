<?php
require_once("argparse.php");
?>
FROM alpine:edge

# for dbg
RUN echo -ne 'https://mirrors.ustc.edu.cn/alpine/edge/main\nhttps://mirrors.ustc.edu.cn/alpine/edge/community\n' > /etc/apk/repositories

# install dependencies
RUN apk add --no-cache vim alpine-sdk xz autoconf automake bison re2c \
    linux-headers clang-dev clang lld cmake zlib-dev zlib-static \
    bzip2-dev bzip2-static

# setup common environs
ENV CC=clang
ENV CXX=clang++
ENV LD=ld.lld

ENV CFLAGS='<?php if(!defined("CFLAGS")){ ?> -fno-ident -march=nehalem -mtune=haswell -Os <?php }else{ echo CFLAGS; }?>'
ENV CXXFLAGS='<?php if(!defined("CXXFLAGS")){ ?><?php }else{ echo CXXFLAGS; }?>'

# make dirs
RUN mkdir -p /usr/lib /usr/include && \
    ln -s /usr/lib /usr/lib64

# prepare lib dependencies
<?php
    echo Dep::$prepares;
    echo Dep::$builds;
?>


# prepare PHP codes
COPY <?php echo $php_dir; ?> /work/php/

# prepare extension codes
<?php echo Ext::$prepext; ?>

# configure php
RUN cd /work/php && \
    ./buildconf --force && \
    ./configure \
        --disable-all \
        --disable-cgi \
        --disable-cli \
        --disable-phpdbg \
        --enable-micro \
        --without-pear \
        --disable-shared \
        --enable-static \
        <?php if(Dep::find("libxml")){ ?>\
        --disable-dom \
        --disable-simplexml \
        --disable-xml \
        --disable-xmlreader \
        --disable-xmlwriter \
        <?php } ?>\
        <?php echo Ext::$extstr ?> && \
    :

# build PHPmicro
RUN cd /work/php && \
    make \
        EXTRA_LIBS="<?php echo Ext::$libstr; ?>" \
        -j$(nproc) && \
    elfedit --output-osabi linux sapi/micro/micro.sfx

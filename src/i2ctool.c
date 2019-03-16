#include <stdlib.h>
#include <string.h>
#include <stdio.h>
#include <unistd.h>
#include <time.h>
#include <stdint.h>
#include <fcntl.h>
#include <sys/ioctl.h>
#include <linux/i2c-dev.h>

int main(int argc, char *argv[])
{
	int fp = -1;
	char filename[32];
	char *p;
	long iChannel = strtol(argv[1], &p, 10);
	long iAddr = strtol(argv[2], &p, 10);
	long op = strtol(argv[3], &p, 10);
	
	sprintf(filename, "/dev/i2c-%d", iChannel);
	if ((fp = open(filename, O_RDWR)) < 0)
		return 1;

	if (ioctl(fp, I2C_SLAVE, iAddr) < 0)
		return 2;

	if( op == 1 )
	{
		long reg = strtol(argv[4], &p, 10);
		long len = strtol(argv[5], &p, 10);
		unsigned char buf[len];
		buf[0] = reg;
		write(fp, buf, 1);
		read(fp, buf, len);
		for(int i=0; i<len; i++)
			printf("%02x ",buf[i]);
		printf("\n");
		return 0;
	}
	if( op == 2 )
	{
		long reg = strtol(argv[4], &p, 10);
		int len = argc - 4;
		unsigned char buf[len];
		buf[0] = reg;
		for(int i=5; i<argc; i++)
			buf[i-4] = (unsigned char)strtol(argv[i], &p, 10);
		write(fp, buf, len);
		return 0;
	}
	
	return 3;
}

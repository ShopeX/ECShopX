package io

import "io"

// TeeReader reads from r and writes to w
type TeeReader struct {
	reader io.Reader
	writer io.Writer
}

// NewTeeReader returns a Reader that writes to w what it reads from r
func NewTeeReader(r io.Reader, w io.Writer) io.Reader {
	return &TeeReader{
		reader: r,
		writer: w,
	}
}

// Read implements io.Reader
func (t *TeeReader) Read(p []byte) (n int, err error) {
	n, err = t.reader.Read(p)
	if n > 0 {
		t.writer.Write(p[:n])
	}
	return n, err
}

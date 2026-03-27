package main

import "fmt"

func main() {
	result := 42
	result = 24     // ineffassign: ineffectual assignment
	message := "Hello"
	
	fmt.Println(result) // use result to avoid unused var error
	fmt.Println(messag) // typecheck: undefined variable
}